<?php
if (!defined('PLUGINS_ADMINISTRATION')) {
    require_once('pre.php');
    header('Location: '.get_server_url().'/plugins/pluginsadministration/');
    exit(0);
}
function getHelp($section = '') {
    if (trim($section) !== '' && $section{0} !== '#') {
        $section = '#'.$section;
    }
    return '<a href="javascript:help_window(\''.get_server_url().'/plugins/pluginsadministration/documentation/'.user_get_languagecode().'/'.$section.'\');">[?]</a>';
}

?>
