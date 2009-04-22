<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * $Id$
 *
 * front-end to plugin DataGenerator */
 
require_once('pre.php');
require_once('common/plugin/PluginManager.class.php');
$plugin_manager =& PluginManager::instance();
$p =& $plugin_manager->getPluginByName('themediff');
if ($p && $plugin_manager->isPluginAvailable($p)) {
    $theme_list = array();
    $is_custom = array();
    $theme_dirs = array($GLOBALS['sys_themeroot'], $GLOBALS['sys_custom_themeroot']);
    while (list(,$dirname) = each($theme_dirs)) {
        // before scanning the directory make sure it exists to avoid warning messages
        if (is_dir($dirname)) {
            $dir = opendir($dirname);
            while ($file = readdir($dir)) {
                if (is_dir("$dirname/$file") && $file != "." && $file != ".." && $file != "CVS" && $file != "custom" && $file != ".svn") {
                    if (is_file($dirname.DIRECTORY_SEPARATOR.$file.DIRECTORY_SEPARATOR.$file.'_Theme.class.php')) {
                        $theme_list[$file] = $dirname.DIRECTORY_SEPARATOR.$file.DIRECTORY_SEPARATOR.'images';
                        $is_custom[$file] = ($GLOBALS['sys_custom_themeroot'] == $dirname);
                    }
                }
            }
            closedir($dir);
        }
    }
    $images = $theme_list;
    $all_images = array();
    foreach($images as $key => $value) {
        $images[$key] = array();
        foreach (new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $value, 
                        RecursiveDirectoryIterator::KEY_AS_PATHNAME
                    ), 
                    RecursiveIteratorIterator::CHILD_FIRST) as $file => $info) 
        {
            if (!preg_match('`/.svn/`', $file) && !$info->isDir()) {
                $images[$key][] = substr($file, strlen($theme_list[$key]));
                $all_images[] = substr($file, strlen($theme_list[$key]));
            }
        }
    }
    $all_images = array_unique($all_images);
    sort($all_images);
    echo '<style type="text/css">
    body, th, td {
        font-family:Verdana, sans-serif; 
        font-size:10pt;
    }
    .preview {
        position:fixed;
        right:0;
    }
    </style>';
    echo '<div class="preview">Preview:<br/><iframe src="" name="preview" widht="200" height="200"></iframe></div>';
    echo '<table border="1" cellspacing="0" cellpadding="4">';
    echo '<thead><tr><th></th>';
    foreach(array_keys($images) as $key) {
        echo '<th>'. $key .'</th>';
    }
    echo '</tr><thead>';
    echo '<tbody>';
    $i = 1;
    foreach($all_images as $im) {
        if ($i++ % 10 == 0) {
            echo '<tr><th></th>';
            foreach(array_keys($images) as $key) {
                echo '<th>'. $key .'</th>';
            }
            echo '</tr>';
        }
        echo '<tr>';
        echo '<td>'. $im .'</td>';
        foreach(array_keys($images) as $key) {
            echo '<td style="text-align:center;  background:#'. (in_array($im, $images[$key]) ? 'efffef' : 'fcc') .'">';
            echo (in_array($im, $images[$key]) ? '<a target="preview" href="'. ($is_custom[$key] ? '/custom' : '/themes') .'/'.$key.'/images'.$im.'">Y</a>' : 'N');
            echo '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    header('Location: '.get_server_url());
}



?>
