<?php
require_once('pre.php');
PHPWikiPluginRedirector::redirect();
require_once('common/wiki/WikiServiceAdmin.class.php');


$wiki = new WikiServiceAdmin($request->get('group_id'));

$wiki->process();
