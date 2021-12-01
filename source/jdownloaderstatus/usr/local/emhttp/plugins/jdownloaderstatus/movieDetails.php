
<style>
body {
    padding: 25px;
}

.roles {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
}

.role {
    width: 200px;
    height: 200px;
}

.role .avatar {
    backgorund-position: center;
    border-radius: 50%;
    overflow: hidden;
    height: 75px;
    width: 75px;
    background-position: center;
    background-repeat: no-repeat;
    background-size: cover;
}

</style>
<?php
    include('/usr/local/emhttp/plugins/jdownloaderstatus/includes/config.php');

    if (!empty($cfg['TOKEN']) && isset($_GET['details'])) {
        $host =  $_GET['host'];
        $url = urldecode($host) . urldecode($_GET['details']) . '?X-Plex-Token=' . $cfg['TOKEN'];
        $details = getUrl($url);
        $video = $details['Video'];
        $videoAttr = $video['@attributes'];
        $title = $videoAttr['title'];
        $directors = [];
        $genres = [];

        if (isset($video['Genre']['@attributes'])) {
            $video['Genre'] = [$video['Genre']];
        }
        if (isset($video['Director']['@attributes'])) {
            $video['Director'] = [$video['Director']];
        }
        if (isset($video['Genre'])) {
            foreach ($video['Genre'] as $genre) {
                array_push($genres, $genre['@attributes']['tag']);
            }
        }
        $director = [];
        if (isset($video['Director'])) {
            foreach($video['Director'] as $director) {
                array_push($directors, $director['@attributes']['tag']);
            }
        }
        echo('
            <h1>' . $title .'</h1>
            <p>' . $videoAttr['summary'] . '</p><p>
            <strong>Year:</strong> ' .$videoAttr['year'] . '<br/>
        ');

        if (isset($videoAttr['studio'])) {
            echo('<strong>Studio:</strong> ' . $videoAttr['studio'] . '<br/>');
        }
        if (count($directors) > 0) {
            echo('<strong>Director:</strong> ' .implode(' / ', $directors) .'<br/>');
        }
        if (count($genres) > 0) {
            echo('<strong>Genre:</strong> ' . implode(' / ', $genres) . '<br/>');
        }
        echo('<strong>Rating:</strong> ' .$videoAttr['contentRating'] . '</p>');

        
        //echo('<div class="roles">');
        echo('<p>');
        if (isset($video['Role'])) {
            echo('<h2>Cast</h2>');
            foreach($video['Role'] as $role) {
            echo($role['@attributes']['tag'] . ' as ' . $role['@attributes']['role'] . '<br/>');
            //     $imageUrl = str_replace('http:', 'https:', $role['@attributes']['thumb']);
            //     echo('
            //         <div class="role">
            //             <div class="avatar" style="background-image:url(' .$imageUrl .');"></div>
            //             <div>' .$role['@attributes']['Tag']  . '</div>
            //         </div>');
            }
            echo('</p>');
        }
        //echo('</div>');
    }


    function getUrl($url) {
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
        return json_decode(json_encode(simplexml_load_string(file_get_contents($url, false, stream_context_create($arrContextOptions)))), TRUE);
    }

    function v_d($obj) {
        echo('<pre>');
        var_dump($obj);
        echo('</pre>');
    }
