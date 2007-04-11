<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: massmail.php 2974 2006-04-21 16:32:20Z guerin $

require_once('pre.php');    

$request =& HTTPRequest::instance();

$Language->loadLanguageMsg('admin/admin');

session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>$Language->getText('admin_massmail','title')));

$src = $title = '';

switch($request->get('tool')) {
    case 'munin':
        $title = 'munin';
        $src   = 'munin/';
        break;
    case 'phpMyAdmin':
        $title = 'phpMyAdmin';
        $src   = 'phpMyAdmin/';
        break;
    case 'info':
        $title = 'PHP info';
        $src   = 'info.php';
        break;
    default:
        break;
}
if ($src) {
    echo '<h1>'. $title .'</h1>';
    $HTML->iframe('/'. $src, array('class' => 'iframe_service'));
}
$HTML->footer(array());
?>
