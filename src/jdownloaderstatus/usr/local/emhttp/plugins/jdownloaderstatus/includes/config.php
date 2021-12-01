<?php
    $plugin = "jdownloaderstatus";
    $plg_path = "/boot/config/plugins/" . $plugin;
    $cfg_file    = "$plg_path/" . $plugin . ".cfg";

    if (file_exists($cfg_file)) {
        $cfg  = parse_ini_file($cfg_file);
    } else {
        $cfg = array();
    }
?>