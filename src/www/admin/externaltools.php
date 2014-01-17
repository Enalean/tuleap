<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    

$request =& HTTPRequest::instance();


session_require(array('group'=>'1','admin_flags'=>'A'));

$src = $title = '';

switch($request->get('tool')) {
    case 'munin':
        $title = 'munin';
        $src   = '/munin/';
        break;
    case 'phpMyAdmin':
        $title = 'phpMyAdmin';
        $src   = '/phpMyAdmin/';
        break;
    case 'APC':
        $title = 'APC';
        $src   = '/admin/apc.php';
        break;
    case 'info':
        $title = 'PHP info';
        $src   = '/info.php';
        break;
    default:
        break;
}
$params = array('tool'=>$request->get('tool'), 'title'=>&$title, 'src'=>&$src);
$em =& EventManager::instance();
$em->processEvent('site_admin_external_tool_selection_hook', $params);

$HTML->header(array('title'=>$title));

if ($src) {
    echo '<h1>'. $title .'</h1>';
    $HTML->iframe($src, array('class' => 'iframe_service', 'width' => '100%', 'height' => '650px'));
}
$HTML->footer(array());
?>
