<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: logout.php 5240 2007-03-12 15:14:31 +0000 (Mon, 12 Mar 2007) nterray $

require_once('pre.php');    
require_once('common/include/CookieManager.class.php');

if (isset($session_hash)) {
    session_delete($session_hash);
}
$cookie_manager =& new CookieManager();
$cookie_manager->removeCookie('session_hash');
session_redirect('/');

?>
