<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('common/include/CookieManager.class.php');

UserManager::instance()->logout();
session_redirect('/');

?>
