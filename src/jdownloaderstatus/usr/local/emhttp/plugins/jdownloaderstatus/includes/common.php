<?php
    define('OS_VERSION', 'Unraid ' . $GLOBALS['unRaidSettings']['version']);
    define('PLUGIN_VERSION', 'v1.1.1');

    function getGeo($ip) {
        $url = 'https://plex.tv/api/v2/geoip?ip_address=' . $ip;
        $resp = getUrl($url);
        if (isset($resp['@attributes'])) {
            return $resp['@attributes']['city'] . ', ' . $resp['@attributes']['subdivision'] . ' ' . $resp['@attributes']['code'];
        }
    }
    function getServers($cfg) {
        $url = 'https://plex.tv/devices.xml?X-Plex-Token=' . $cfg['TOKEN'];
        $url2 = 'https://plex.tv/api/resources?X-Plex-Token=' .$cfg['TOKEN'] . ($cfg['FORCE_PLEX_HTTPS'] === '1' ? '&includeHttps=1' : '');
        if (isset($_REQUEST['dbg'])) {
            v_d($url);
        }
        $servers = getUrl($url);
        if ($servers !== false) {
            $serverList = [];
            if (isset($servers['@attributes'])) {
                $servers = [$servers];
            }
            foreach($servers as $server) {
                if (isset($server['Device']['@attributes'])) {
                    $server['Device'] = [$server['Device']];
                }
                foreach($server['Device'] as $device) {
                    if (isset($device['@attributes']['provides'])) {
                        $providers = explode(',', $device['@attributes']['provides']);
                        if (in_array('server', $providers)) {
                            $serverList[$device['@attributes']['clientIdentifier']] = [
                                'Name' => $device['@attributes']['name'],
                                'Identifier' => $device['@attributes']['clientIdentifier'],
                                'Connections' => []
                            ];
                        }
                    }
                }
                if (count($serverList) > 0) {
                    $connections = getUrl($url2);
                    if ($connections !== false) {
                        foreach($connections['Device'] as $device) {
                            $identifier = $device['@attributes']['clientIdentifier'];
                            if (isset($serverList[$identifier])) {
                                foreach($device['Connection'] as $connection) {
                                    array_push($serverList[$identifier]['Connections'], $connection['@attributes']);
                                }
                            }
                        }
                    }
                }
            }
        } else {
            return false;
        }

        return $serverList;
    }

    function getServerCheckboxes($cfg) {
        $servers = getServers($cfg);
        $retVal = '<div id="HOST">';
        $selected = explode(',', $cfg['HOST']);
        foreach($servers as $server) {
            foreach($server['Connections'] as $connection) {
                $url = $connection['uri'];
                $retVal .= '<input onchange="updateServerList(\'HOST\')" name="hostbox" data-id="' . $server['Identifier'] . '" id="' .$url .'" type="checkbox" value="'  .$url .'"' .(in_array($url, $selected) ? ' checked="checked"' : '') . '> <label for="' . $url . '"/>' .$server['Name'] .' (' . $connection['address'] . ':' .$connection['port'] .')' . ($connection['local'] === '0' ? ' - Remote' : '') . '</label></br>';
            }
        }

        $retVal .= '</div>';
        

        return $retVal;
    }

    function generateServerList($cfg, $name, $id, $selected) {
        $servers = getServers($cfg);
        $retVal = '
                <select name="' .$name . '" id="' .$id .'">
        ';
        foreach($servers as $server) {
            foreach($server['Connections'] as $connection) {
                $url = $connection['uri'];
                $retVal .= '<option value="'  .$url .'"' .($selected === $url ? ' selected="selected"' : '') . '>' .$server['Name'] .' (' . $connection['address'] . ':' .$connection['port'] .')' . ($connection['local'] === '0' ? ' - Remote' : '') . '</option>';
            }
        }
        $retVal .= '</select>';

        return $retVal;
    }

    function getStreams($cfg) {        
        $hosts = explode(',', $cfg['HOST']);
        $extraHosts = explode(',', $cfg['CUSTOM_SERVERS']);
        $hosts = array_merge($hosts, $extraHosts);

        $streams = [];
        $schedules = [];
        foreach($hosts as $host) {
            $streams[] = $host . "/status/sessions?X-Plex-Token=" . $cfg['TOKEN'] .'&_m=' .mktime();
            $schedules[] = $host ."/media/subscriptions/scheduled?X-Plex-Token=" .$cfg['TOKEN'];
            if (isset($_REQUEST['dbg'])) {
                v_d($streams);
                v_d($schedules);
            }
        }
        $combined = $streams;
        array_push($combined , ...$schedules);
        $responses = getUrl($combined);

        return $responses;
    }

    function v_d($obj) {
        echo('<pre>');
        var_dump($obj);
        echo('</pre>');
    }

    function getUrl($urls) {
        if (is_array($urls)) {
            $rets = [];
            $multi = [];
            $mh = curl_multi_init();
            foreach($urls as $idx=>$url) {
                $prefix = '';
                if (stripos($url, 'sessions') !== false) {
                    $prefix = 'streams-';
                } else if (stripos($url, 'schedule') !== false) {
                    $prefix = 'schedules-';
                }
                    
                $id = $prefix . $idx;
                $multi[$id] = curl_init();
                curl_setopt($multi[$id], CURLOPT_URL, $url);
                curl_setopt($multi[$id], CURLOPT_HEADER, 0);
                curl_setopt($multi[$id], CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($multi[$id], CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($multi[$id], CURLOPT_SSL_VERIFYSTATUS, 0);
                curl_setopt($multi[$id], CURLOPT_CONNECTTIMEOUT, 30);
                curl_setopt($multi[$id], CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($multi[$id], CURLOPT_RETURNTRANSFER, 1);
                curl_multi_add_handle($mh, $multi[$id]);
            }
            //execute the handles
            do {
                $mrc = curl_multi_exec($mh, $active);
            }
            while ($mrc == CURLM_CALL_MULTI_PERFORM);

            while ($active && $mrc == CURLM_OK) {
                if (curl_multi_select($mh) != -1) {
                    do {
                        $mrc = curl_multi_exec($mh, $active);
                    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
                }
            }
            
            foreach($multi as $idx=>$m) {
                if (isset($_REQUEST['dbg'])) {
                    v_d(curl_multi_getcontent($multi[$idx]));
                }
                $urlParts = parse_url(curl_getinfo($multi[$idx],CURLINFO_EFFECTIVE_URL));
            
                $url = $urlParts['scheme'] . '://' . $urlParts['host'] .':' . $urlParts['port'] . $urlParts['path'] . '?' . $urlParts['query'];
                $rets[$idx]['url'] = $url;
                $content = json_decode(json_encode(simplexml_load_string(curl_multi_getcontent($multi[$idx]))), TRUE);
                $rets[$idx]['content'] = $content;

                curl_multi_remove_handle($mh, $m);
            }
            
            curl_multi_close($mh);
            return $rets;
        } else {
            $arrContextOptions=array(
                "http" => array(
                    "method" => "GET",
                    "header" => 
                        "Content-Type: application/xml; charset=utf-8;\r\n".
                        "Connection: close\r\n".
                        "Cache-Control: no-cache, no-store, must-revalidate, max-age=0\r\n".
                        "Pragma: no-cache\r\n",
                    "ignore_errors" => true,
                    "timeout" => (float)30.0
                ),
                "ssl"=>array(
                    "allow_self_signed"=>true,
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                )
            );
            $rets = json_decode(json_encode(simplexml_load_string(file_get_contents($urls, false, stream_context_create($arrContextOptions)))), TRUE);
        }

        return $rets;
    }

    function mergeStreams($allStreams) {
        $mergedStreams = [];
        
        $videoStreams = [];
        $schedules = [];
        foreach($allStreams as $idx=>$details) {
            $urlParts = parse_url($details['url']);
            $source = $details['content'];
            $source['@host'] = $urlParts['scheme'] . '://' . $urlParts['host'] . ':' . $urlParts['port'];
            if (stripos($idx, 'streams-') !== false) {
                $videoStreams[] = $source;
            } else if (stripos($idx, 'schedules-') !== false) {
                $schedules[] = $source;
            }
        }

        foreach($videoStreams as $streams) {
            if (isset($streams['Video'])) {
                if (isset($streams['Video']) && isset($streams['Video']['@attributes'])) {
                    $streams['Video'] = [$streams['Video']];
                }
                foreach($streams['Video'] as $idx=>$video) {
                    if (isset($video['Media']['@attributes'])) {
                        $video['Media'] = [$video['Media']];
                    }
                    foreach($video['Media'] as $media) {
                        if ($media['@attributes']['selected'] === '1') {
                            if (!isset($media['@attributes']['channelCallSign'])) {
                                $title = $video['@attributes']['title'] . ' (' . $video['@attributes']['year'] . ')';
                                if (isset($video['@attributes']['parentTitle'])) {
                                    $title = $video['@attributes']['parentTitle'] . ' - ' . $title;
                                }
                                if (isset($video['@attributes']['grandparentTitle'])) {
                                    $title = $video['@attributes']['grandparentTitle'] . ' - ' . $title;
                                }
                            } else  {
                                $title = $media['@attributes']['channelCallSign'] . ' (' . $media['@attributes']['channelIdentifier'] .') - ' . $video['@attributes']['grandparentTitle'];
                            }
                            if (isset($media['Part']['@attributes']['duration'])) {
                                $duration = $media['Part']['@attributes']['duration'];
                                $lengthInSeconds = $duration / 1000;
                                $lengthInMinutes = ceil($lengthInSeconds / 60 );
                                $lengthSeconds = floor($lengthInSeconds%60);
                                $lengthMinutes = floor(($lengthInSeconds%3600)/60);
                                $lengthHours = floor(($lengthInSeconds%86400)/3600);
                                
                                $currentPosition = floatval((int)$video['@attributes']['viewOffset']);
                                $currentPositionInSeconds = $video['@attributes']['viewOffset'] / 1000;
                                $currentPositionInMinutes = ceil($currentPositionInSeconds / 60);
                                $currentPositionSeconds = floor($currentPositionInSeconds%60);
                                $currentPositionMinutes = floor(($currentPositionInSeconds%3600)/60);
                                $currentPositionHours = floor(($currentPositionInSeconds%86400)/3600);
                            } else {
                                $duration = null;
                            }
                            $artThumb = $video['@attributes']['art'];
                            if (isset($media['@attributes']['channelThumb'])) {
                                $artThumb = $media['@attributes']['channelThumb'];
                            }

                            $mergedStream = [
                                '@host' => $streams['@host'],
                                'id' => $media['@attributes']['id'],
                                'type' => 'video',
                                'player' => $video['Player']['@attributes']['product'],
                                'title' => $title,
                                'key' => $video['@attributes']['key'],
                                'duration' => $duration,
                                'artUrl' => '/plugins/plexstreams/getImage.php?img=' . urlencode($artThumb) . '&host=' . urlencode($streams['@host']),
                                'thumbUrl' => '/plugins/plexstreams/getImage.php?img=' .  urlencode($video['@attributes']['grandparentThumb'] ?? $video['@attributes']['thumb']) . '&host=' . urlencode($streams['@host']),
                                'user' => $video['User']['@attributes']['title'],
                                'userAvatar' => $video['User']['@attributes']['thumb'],
                                'state' => $video['Player']['@attributes']['state'],
                                'stateIcon' => 'play',
                                'length' => $duration ?? null,
                                'lengthInSeconds' => $lengthInSeconds ?? null,
                                'lengthInMinutes' => $lengthInMinutes ?? null,
                                'lengthSeconds' => $lengthInSeconds ?? null,
                                'lengthMinutes' => $lengthMinuites ?? null,
                                'lengthHours' => $lengthHours ?? null,
                                'currentPosition' => $currentPosition ?? null,
                                'currentPositionInSeconds' =>  $currentPositionInSeconds ?? null,
                                'currentPositionInMinutes' =>  $currentPositionInMinutes ?? null,
                                'currentPositionHours' => $currentPositionHours ?? null,
                                'currentPositionMinutes' => $currentPositionMinutes ?? null,
                                'currentPositionSeconds' => $currentPositionSeconds ?? null,
                                'location' => $video['Session']['@attributes']['location'],
                                'address' => $video['Player']['@attributes']['address'],
                                'bandwidth' => round((int)$video['Session']['@attributes']['bandwidth'] / 1000, 1),
                                'streamInfo' => []
                            ];
                            $loc = strtoupper($mergedStream['location']);
                            $mergedStream['locationDisplay'] = $loc . ' (' . $mergedStream['address'] . ($loc !== 'LAN' ? ' - ' .getGeo($mergedStream['address']) : '' ) . ')';
                            
                            if ($mergedStream['duration'] !== null) {
                                $mergedStream['percentPlayed'] = round(($currentPositionInMinutes/ $lengthInMinutes) * 100, 0);
                                $mergedStream['currentPositionDisplay'] = str_pad($currentPositionHours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($currentPositionMinutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($currentPositionSeconds, 2, '0', STR_PAD_LEFT);
                                $mergedStream['lengthDisplay'] = str_pad($lengthHours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($lengthMinutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($lengthSeconds, 2, '0', STR_PAD_LEFT);
                            } else {
                                $mergedStream['percentPlayed'] = 0;
                            }

                            if ($mergedStream['state'] === 'paused') {
                                $mergedStream['stateIcon'] = 'pause';
                            } else if ($mergedStream['state'] !== 'playing') {
                                $mergedStream['stateIcon'] = 'buffer';
                            }

                            foreach ($media['Part']['Stream'] as $stream) {
                                if ($stream['@attributes']['streamType'] === '2') {
                                    $mergedStream['streamInfo']['audio'] = $stream;
                                    $mergedStream['streamInfo']['audio']['@attributes']['decision'] = $mergedStream['streamInfo']['audio']['@attributes']['decision'] ?? 'direct play';
                                } else if ($stream['@attributes']['streamType'] === '1') {
                                    $mergedStream['streamInfo']['video'] = $stream;
                                    $mergedStream['streamInfo']['video']['@attributes']['decision'] = $mergedStream['streamInfo']['video']['@attributes']['decision'] ?? 'direct play';
                                }
                            }
                            
                            $mergedStream['streamDecision'] = $media['Part']['@attributes']['decision'];
                            if ($mergedStream['streamDecision'] === 'directplay') {
                                $mergedStream['streamDecision'] = 'Direct Play';
                            }

                            if ($mergedStream['streamDecision'] === 'transcode') {
                                if ($mergedStream['streamInfo']['video']['@attributes']['decision'] === 'transcode') {
                                    $mergedStream['streamInfo']['video']['@attributes']['decision'] .= $video['TranscodeSession']['@attributes']['transcodeHwRequested'] === '1' ?  ' (HW)' : '' . '<br/>' . $mergedStream['streamInfo']['video']['@attributes']['displayTitle'] . ' -> ' . $media['@attributes']['videoResolution'];
                                }
                                if ($mergedStream['streamInfo']['audio']['@attributes']['decision'] === 'transcode') {
                                    $mergedStream['streamInfo']['audio']['@attributes']['decision'] .= ' (' . $video['TranscodeSession']['@attributes']['sourceAudioCodec'] . ' -> ' . $video['TranscodeSession']['@attributes']['audioCodec'] .')';
                                }
                            }

                            $mergedStreams[] = $mergedStream;
                        }
                    }
                }
            }
            if (isset($streams['Track'])) {
                if (isset($streams['Track']) && isset($streams['Track']['@attributes'])) {
                    $streams['Track'] = [$streams['Track']];
                }
                foreach($streams['Track'] as $idx=>$audio) {
                    if (isset($audio['Media']['@attributes'])) {
                        $audio['Media'] = [$audio['Media']];
                    }
                    
                    foreach($audio['Media'] as $media) {
                        if (isset($media['Part']) && isset($media['Part']['@attributes'])) {
                            $media['Part'] = [$media['Part']];
                        }
                        foreach($media['Part'] as $part) {
                            if (isset($part['Stream']) && isset($part['Stream']['@attributes'])) {
                                $part['Stream'] = [$part['Stream']];
                            }
                            foreach ($part['Stream'] as $stream) {
                                if ($stream['@attributes']['selected'] === '1') {
                                    $title = $audio['@attributes']['title'] . ' - ' . $audio['@attributes']['originalTitle'] . '<br/><span style="font-size:8px;">' . $audio['@attributes']['parentTitle'] . '</span>';
                                    $duration = $part['@attributes']['duration'];
                                    $lengthInSeconds = $duration / 1000;
                                    $lengthInMinutes = ceil($lengthInSeconds / 60 );
                                    $lengthSeconds = floor($lengthInSeconds%60);
                                    $lengthMinutes = floor(($lengthInSeconds%3600)/60);
                                    $lengthHours = floor(($lengthInSeconds%86400)/3600);
                                    $currentPosition = floatval((int)$audio['@attributes']['viewOffset']);
                                    $currentPositionInSeconds = $audio['@attributes']['viewOffset'] / 1000;
                                    $currentPositionInMinutes = ceil($currentPositionInSeconds / 60);
                                    $currentPositionSeconds = floor($currentPositionInSeconds%60);
                                    $currentPositionMinutes = floor(($currentPositionInSeconds%3600)/60);
                                    $currentPositionHours = floor(($currentPositionInSeconds%86400)/3600);

                                    $mergedStream = [
                                        '@host' => $streams['@host'],
                                        'id' => $media['@attributes']['id'],
                                        'type' => 'audio',
                                        'player' => $audio['Player']['@attributes']['product'],
                                        'title' => $title,
                                        'key' => $audio['@attributes']['key'],
                                        'duration' => $duration,
                                        'artUrl' => '/plugins/plexstreams/getImage.php?img=' . urlencode($audio['@attributes']['art']) . '&host=' . urlencode($streams['@host']),
                                        'thumbUrl' => '/plugins/plexstreams/getImage.php?img=' .  urlencode($audio['@attributes']['grandparentThumb'] ?? $audio['@attributes']['thumb']) . '&host=' . urlencode($streams['@host']),
                                        'user' => $audio['User']['@attributes']['title'],
                                        'userAvatar' => $audio['User']['@attributes']['thumb'],
                                        'state' => $audio['Player']['@attributes']['state'],
                                        'stateIcon' => 'play',
                                        'length' => $duration,
                                        'lengthInSeconds' => $lengthInSeconds,
                                        'lengthInMinutes' => $lengthInMinutes,
                                        'lengthSeconds' => $lengthInSeconds,
                                        'lengthMinutes' => $lengthMinuites,
                                        'lengthHours' => $lengthHours,
                                        'currentPosition' => $currentPosition,
                                        'currentPositionInSeconds' =>  $currentPositionInSeconds,
                                        'currentPositionInMinutes' =>  $currentPositionInMinutes,
                                        'currentPositionHours' => $currentPositionHours,
                                        'currentPositionMinutes' => $currentPositionMinutes,
                                        'currentPositionSeconds' => $currentPositionSeconds,
                                        'percentPlayed' => $lengthInMinutes > 0 ? round(($currentPositionInMinutes/ $lengthInMinutes) * 100, 0) : '',
                                        'currentPositionDisplay' => str_pad($currentPositionHours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($currentPositionMinutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($currentPositionSeconds, 2, '0', STR_PAD_LEFT),
                                        'lengthDisplay' => str_pad($lengthHours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($lengthMinutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($lengthSeconds, 2, '0', STR_PAD_LEFT),
                                        'location' => $audio['Session']['@attributes']['location'],
                                        'address' => $audio['Player']['@attributes']['address'],
                                        'bandwidth' => round((int)$audio['Session']['@attributes']['bandwidth'] / 1000, 1),
                                        'streamInfo' => []
                                    ];
                                    if ($mergedStream['location'] === null) {
                                        if ($audio['Player']['@attributes']['local'] == "1") {
                                            $mergedStream['location'] = 'LAN';
                                        }
                                    }

                                    $mergedStream['locationDisplay'] = $loc . ' (' . $mergedStream['address'] . ($loc !== 'LAN' ? ' - ' .getGeo($mergedStream['address']) : '' ) . ')';

                                    if ($mergedStream['state'] === 'paused') {
                                        $mergedStream['stateIcon'] = 'pause';
                                    } else if ($mergedStream['state'] !== 'playing') {
                                        $mergedStream['stateIcon'] = 'buffer';
                                    }
                                    if (isset($part['@attributes']['decision'])) {
                                        $mergedStream['streamDecision'] = $part['@attributes']['decision'];
                                    } else {
                                        $mergedStream['streamDecision'] = 'Direct Play';
                                    }
                                    if ($mergedStream['streamDecision'] === 'directplay') {
                                        $mergedStream['streamDecision'] = 'Direct Play';
                                    }

                                    $mergedStream['streamInfo']['audio'] = $stream;
                                    $mergedStream['streamInfo']['audio']['@attributes']['decision'] = $mergedStream['streamInfo']['audio']['@attributes']['decision'] ?? 'direct play';

                                    $mergedStreams[] = $mergedStream;
                                }
                            }
                        }
                    }
                }
            }
        }

        // if (isset($scheduled) && isset($scheduled['@attributes'])) {
        //     $streams['Scheduled'] = [$streams['Scheduled']];
        //     foreach($streams['Scheduled'] as $scheduled) {

        //     }
        // }

        return $mergedStreams;
    }

?>