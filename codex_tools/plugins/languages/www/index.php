<?php
require_once('pre.php');
require_once('common/include/HTTPRequest.class.php');
$request =& HTTPRequest::instance();

$available_languages_dir = glob($GLOBALS['sys_incdir'] .'/*', GLOB_ONLYDIR);
$available_languages     = array_map('basename', $available_languages_dir);
$excludes = '`^('. implode('|', array(
    preg_quote('.'),
    preg_quote('..'),
    preg_quote('.svn'),
    '.*~',
)) .')$`';

function search_files($dir, $prefix = '') {
    global $excludes;
    
    $files = array();
    if ($dh = opendir($dir)) {
       while (($file = readdir($dh)) !== false) {
           if (!preg_match($excludes, $file)) {
               if (is_dir($dir .'/'. $file)) {
                   $files = array_merge($files, search_files($dir .'/'. $file, $prefix . $file .'/'));
               } else {
                   $files[] = $prefix . $file;
               }
           }
       }
       closedir($dh);
    }
    return $files;
}
$files = search_files($available_languages_dir[0]);
usort($files, 'strnatcasecmp');


if ($request->exist('value') || $request->exist('load')) {
    $value = 'error';
    if ($request->exist('txt')) {
        list($lang, $file) = each($request->get('txt'));
        if (in_array($file, $files)) {
            $file_name = $GLOBALS['sys_incdir'] .'/'. $lang .'/'. $file;
            if ($request->exist('value')) {
                $value = utf8_decode($request->get('value'));
                if ($f = fopen($file_name, 'w')) {
                    fwrite($f, $value);
                    fclose($f);
                }
            } else if ($request->exist('load')) {
                $value = file_get_contents($file_name);
            }
        }
    } else if ($request->exist('tab')) {
        list($lang, $f) = each($request->get('tab'));
        list($file, $line_number) = each($f);
        if (in_array($file, $files)) {
            $value = utf8_decode($request->get('value'));
            $file_name = $GLOBALS['sys_incdir'] .'/'. $lang .'/'. $file;
            $ary = file($file_name);
            $line = explode("\t", $ary[$line_number], 3);
            $line[2] = $value ."\n";
            $ary[$line_number] = implode("\t", $line);
            if ($f = fopen($file_name, 'w')) {
                fwrite($f, implode('', $ary));
                fclose($f);
            }
        }
    }
    echo $value;
} else {
    if ($request->exist('tabadd') && $request->get('k1') && $request->get('k2')) {
        foreach($available_languages as $lang) {
            if ($f = fopen($GLOBALS['sys_incdir'] .'/'. $lang .'/'. $request->get('openfile'), 'a')) {
                fwrite($f, "\n". $request->get('k1') ."\t". $request->get('k2') ."\t\n");
                fclose($f);
            }
        }
    }
    $GLOBALS['HTML']->includeJavascriptFile('/scripts/prototype/prototype.js');
    $GLOBALS['HTML']->includeJavascriptFile('/scripts/scriptaculous/scriptaculous.js');
    $GLOBALS['HTML']->includeJavascriptFile('/scripts/scriptaculous/extensions.js');
    
    $GLOBALS['HTML']->header(array('title' => 'Edit language files'));
    echo '<form action="" method="POST">';
    echo '<select name="openfile">';
    if (!$request->get('openfile')) {
        echo '<option value="-1">Choose file...</option>';
    }
    foreach($files as $f) {
        echo '<option '. ($f == $request->get('openfile') ? 'selected="selected"' : '') .' >'. $f .'</option>';
    }
    echo '</select>';
    echo '<input type="submit" value="Open" />';
    echo '</form>';
    echo '<style type="text/css">
    .editor_field {
        width:100%;
    }
    .inplaceeditor-empty {
        font-style: italic;
        color: #999;
    }
    </style>';
    if ($request->exist('openfile') && in_array($request->get('openfile'), $files)) {
        $path_parts = pathinfo($request->get('openfile'));
        switch($path_parts['extension']) {
            case 'tab':
                $titles = array_merge(array('Key'), $available_languages);
                $width = (int)(100 / (count($available_languages) + 1));
                echo html_build_list_table_top($titles, false, false, true, null, "") . "\n";
                foreach($available_languages as $lang) {
                    $ary = file($GLOBALS['sys_incdir'] .'/'. $lang .'/'. $request->get('openfile'));
                    for( $i=0; $i<sizeof($ary); $i++) {
                        if (substr($ary[$i], 0, 1) == '#' ||  //ignore comments...
                                strlen(trim($ary[$i])) == 0) {    //...or empty lines
                            continue;
                        }
                        if (!preg_match("/^include ([a-zA-Z]+)/", $ary[$i], $matches)) {
                            $line = explode("\t", $ary[$i], 3);
                            $file[$lang][$line[0] ."\t". $line[1]] = array(
                                'line'  => $i,
                                'value' => chop(str_replace('\n', "\n", ($line[2]))),
                            );
                        }
                    }
                }
                $keys = array();
                foreach($available_languages as $lang) {
                    $keys = array_merge($keys, array_keys($file[$lang]));
                }
                $keys = array_unique($keys);
                $i = 0;
                foreach($keys as $key) {
                    if ($i != 0 && $i % 10 == 0) {
                        echo '<tr class="boxtable"><td class="boxtitle">'. implode('</td><td class="boxtitle">', $titles) .'</td></tr>';
                    }
                    echo '<tr class="'. html_get_alt_row_color($i++) .'">';
                    echo '<th style="width:'. $width .'%; vertical-align:top;">'. preg_replace('`\t`', '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $key) .'</th>';
                    foreach($available_languages as $lang) {
                        echo '<td style="width:'. $width .'%; vertical-align:top;">';
                        $element = 'tab_'. $lang .'_'. $file[$lang][$key]['line'];
                        $url     = '?tab['. $lang .']['. $request->get('openfile') .']='. $file[$lang][$key]['line'];
                        echo '<div id="'. $element .'" style="width:100%">';
                        echo $file[$lang][$key]['value'];
                        echo '</div>';
                    echo <<<EOS
<script type="text/javascript">
Event.observe(window, 'load', function() {
        new Ajax.InPlaceEditor('$element', '$url');
});
</script>
EOS;
                        echo '</td>';
                    }
                }
                echo '<tr class="'. html_get_alt_row_color($i++) .'" id="add_tab"><td>';
                echo '<form action="#add_tab" method="POST"><input type="hidden" name="openfile" value="'. $request->get('openfile') .'" />';
                echo '<input type="hidden" name="tabadd" value="1" />';
                echo '<input type="text" name="k1" /> <input type="text" name="k2" /><input type="submit" value="Add" />';
                echo '</form>';
                echo '</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
                echo '</table>';
                break;
            case 'txt':
                $titles = $available_languages;
                $width = (int)(100 / count($available_languages));
                echo html_build_list_table_top($titles, false, false, true, null, "") . "\n";
                echo '<tr class="boxitem">';
                foreach($available_languages as $lang) {
                    $element = 'txt_'. $lang;
                    $url     = '?txt['. $lang .']='. $request->get('openfile');
                    $rows    = count(file($GLOBALS['sys_incdir'] .'/'. $lang .'/'. $request->get('openfile')));
                    $load    = $url .'&load=1';
                    echo '<td style="width:'. $width .'%; vertical-align:top;">';
                    echo '<div id="'. $element .'">';
                    include($GLOBALS['sys_incdir'] .'/'. $lang .'/'. $request->get('openfile'));
                    echo '</div>';
                    echo <<<EOS
<script type="text/javascript">
Event.observe(window, 'load', function() {
        new Ajax.InPlaceEditor('$element', '$url', { rows: $rows, loadTextURL: '$load', stripTags: false });
});
</script>
EOS;
                    echo '</td>';
                }
                echo '</table>';
                foreach($available_languages as $lang) {
                }
                break;
            default:
                break;
        }
    }
    echo '<br />';
    $GLOBALS['HTML']->footer(array());
}
?>