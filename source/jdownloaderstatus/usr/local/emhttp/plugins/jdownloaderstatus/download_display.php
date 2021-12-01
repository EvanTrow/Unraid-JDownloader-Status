<link type="text/css" rel="stylesheet" href="/plugins/jdownloaderstatus/spinner.css">
<style>
    .caution {
        padding-left: 76px;
        margin: 16px -40px;
        padding: 16px 50px;
        background-color:  rgb(254, 239, 227);
        color: rgb(191, 54, 12);
        display: block;
        font-weight: bolder;
        font-size: 14px;
    }
    .caution i {
        font-size:15pt;
    }

    .caution .text {
        display: inline-block;
        vertical-align: 2px;
        padding-left: 7px;
    }

    #streams-container {
        display: inline;
    }

    #streams-container ul{
        display: flex;
        flex-wrap: wrap;
    }

    .stream-container {
        list-style: none;
        flex: 0 0 30%;
        position: relative;
        margin-right: 10px;
        margin-bottom: 10px;
    }

    .stream-subcontainer {
        width: 500px;
        background-color: #000;
    }

    .stream {
        background-position: center;
        background-repeat: no-repeat;
        background-size: contain;
    }

    .blur {
        backdrop-filter: blur(3px);
    }

    .stream .blur {
        width: 100%;
        height: 100%;
    }

    .stream .poster {
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        width: 150px;
        height: 225px;
        z-index: 997;
    }

    .stream-container .bottom-box {
        width: 500px;
        position:absolute;
        bottom: 0;
        background: rgb(70,67,67,0.55);
        color: #fff;
        font-weight: bolder;
        z-index: 998;
    }

    .stream-container .bottom-box .progressBar {
        height: 5px;
        background-color: #cc0000;
    }

    .stream-container .bottom-box .progressBar .position {
        position: absolute;
        right: 5px;
        top: 0;
        width: 100px;
        font-size:9px;
        color: #fff;
        text-align:right;
    }

    .stream-container .bottom-box .title {
        padding: 10px;
        z-index: 999;
    }

    .stream-container .bottom-box .title a {
        text-decoration: none;
        color: #fff;
    }

    .stream-container .bottom-box .title a:hover {
        text-decoration: none;
    }

    .stream-container .title .status {
        float:right;
        color: #fff;
    }

    .userIcon {
        border-radius: 50%;
        overflow: hidden;
        position: absolute;
        top: 5px;
        right: 5px;
        margin: 0;
        height: 75px;
        width: 75px;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
    }

    .details {
        opacity: 0;
        transition: visibility 0s, opacity 0.5s ease-out ;
        position: absolute;
        opacity: 0;
        left: 160px;
        top: 5px;
        background: rgb(34, 34, 34, 0.80);
        color: #fff;
        width: 244px;
        height: 175px;
        font-weight:bold;
    }

    .details:hover {
        opacity: 1;
    }

    .details ul {
        margin-top: 0;
        padding-left: 0;
        list-style: none;
        font-size:14px;
    }
    
    .details li {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        align-items: baseline;
        width:100%;
        margin-bottom:5px;
        box-sizing: border-box;
        color: #fff;
        font-size: 12px;
        line-height: 17px;
    }

    .details li div {
        color: #aaa;
        text-align: right;
        line-height: 14px;
    }
    
    .details li .label {
        color: #aaa;
        width:91px;
    }

    .details li .value {
        color: #fff;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
        flex-grow: 1;
        min-width:165px;
        text-align: left;
        margin-left: 10px;
    }

    .sb-overlay {
        backdrop-filter: blur(7px);
    }

</style>
<script>
    function openBox(cmd,title,height,width,load,func,id) {
    // open shadowbox window (run in foreground)
    var run = cmd.split('?')[0].substr(-4)=='.php' ? cmd : '/logging.htm?cmd='+cmd+'&csrf_token=91E90CB5E22139F9';
    var options = {overlayOpacity: 0.90};
    Shadowbox.open({content:run, player:'iframe', title:title, height:Math.min(height,screen.availHeight), width:Math.min(width,screen.availWidth), options:options});
    }
</script>
<?php
    $plugin = 'jdownloaderstatus';
    $docroot = $docroot ?: $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
    $translations = file_exists("$docroot/webGui/include/Translations.php");
    include('/usr/local/emhttp/plugins/jdownloaderstatus/includes/config.php');
    include('/usr/local/emhttp/plugins/jdownloaderstatus/includes/common.php');

    if ($translations) {
        // add translations
        $_SERVER['REQUEST_URI'] = 'jdownloaderstatus';
        require_once "$docroot/webGui/include/Translations.php";
    } else {
        // legacy support (without javascript)
        $noscript = true;
        require_once "$docroot/plugins/$plugin/includes/Legacy.php";
    }

    $mergedStreams = [];

    if (!empty($cfg['TOKEN'])) {
        $streams = getStreams($cfg);
        
        $mergedStreams = mergeStreams($streams);
        echo('<h4 style="margin-bottom:0px;display:none;" id="hover-message">' . _('Hover the stream for details') . '</h4>');
        if (count($mergedStreams) > 0) {
            echo ('<div id="streams-container"><ul>');            
            foreach($mergedStreams as $idx => $stream) {
                echo('
                    <li class="stream-container" id="' . $stream['id'] . '">
                        <div class="stream-subcontainer">
                            <div class="stream" style="background-image:url(' . $stream['artUrl'] .');">
                                <div class="blur">
                                    <div class="details">
                                        <ul class="detail-list">
                                            <li><div class="label">' . _('Length') . '</div><div class="value">' . $stream['lengthDisplay'] .'</div></li>
                                            <li><div class="label">' . _('Stream') . '</div><div class="stream value">' . ucwords($stream['streamDecision']) .'</div></li>
                                            <li><div class="label">' . _('Location') . '</div><div class="value" title="' . $stream['locationDisplay'] . '" style="pointer:default;">' .$stream['locationDisplay'] .'</div></li>
                                            <li><div class="label">' . _('Bandwidth') . '</div><div class="bandwidth value">' .$stream['bandwidth'] . ' Mbps</div></li>
                                            <li><div class="label">' . _('Audio') . '</div><div class="audio value">' . ucwords($stream['streamInfo']['audio']['@attributes']['decision'] ?? $stream['streamInfo']['audio']['decision']) . '</div></li>
                ');
                if (isset($stream['streamInfo']['video'])) {
                    echo('                  <li><div class="label">' . _('Video') . '</div><div class="video value">' . ucwords($stream['streamInfo']['video']['@attributes']['decision'] ?? $stream['streamInfo']['video']['decision']) . '</div></li>');
                }

                echo('
                                        </ul>
                                    </div>
                                    <div class="poster" style="background-image:url(' .$stream['thumbUrl'] .');">
                                    </div>
                                    <div class="userIcon" title="' .$stream['user'] . '" style="background-image:url(' . $stream['userAvatar'] . ')">
                                    </div>
                                </div>
                            </div>
                            <div class="bottom-box">
                                <div class="progressBar" duration="' . $stream['duration'] .'" style="width:' . 
                                    (!is_null($stream['duration']) ? $stream['percentPlayed'] : '0') .
                                    '%"><div class="position">' . 
                                    (!is_null($stream['duration']) ?  '<span class="currentPositionHours">' .str_pad($stream['currentPositionHours'], 2, 0, STR_PAD_LEFT) . '</span>:<span class="currentPositionMinutes">' . str_pad($stream['currentPositionMinutes'], 2, 0, STR_PAD_LEFT) . '</span>:<span class="currentPositionSeconds">' .str_pad($stream['currentPositionSeconds'], 2, 0, STR_PAD_LEFT) .'</span>  / ' . $stream['lengthDisplay'] : '' ) .'</div></div>
                                <div class="title">' . ($stream['type'] === 'video' ? '<a href="#" onclick="openBox(\'/plugins/jdownloaderstatus/movieDetails.php?details=' . urlencode($stream['key']) . '&host=' .urlencode($stream['@host']) . '\',\'Details\',600,900); return false;">' : '') . $stream['title'] . ($stream['type'] === 'video' ? '</a>' : '' ) . '<div class="status"><i class="fa fa-' .$stream['stateIcon']  . '" title="' .ucwords($stream['state']) .'"></i></div></div>
                            </div>
                        </div>
                    </li>
                ');
            }
            echo('</ul></div>');
            echo('<script>$(\'#hover-message\').show();</script>');
        } else {
            echo('<p style="text-align:center;font-style:italic;" id="no-streams">' . _('There are currently no active streams') . '</p>');
        }
    } else {
        echo('<div class="caution"><i class="fa fa-exclamation-triangle"></i><div class="text">' . _('Please provide server details under Settings -> Network Services -> JDownloader Status or') . ' <a href="/Settings/JDownloaderStatus">' . _('click here') .'</a></div></div>');
    }
?>
<script src="/plugins/jdownloaderstatus/js/plex.js"></script>
<script>
    var title = $('title').html();
    $('title').html(title.split('/')[0] + '/JDownloader Status');
    updateFullStreamInfo();
    setInterval(updateFullStreamInfo, 5000);
</script>