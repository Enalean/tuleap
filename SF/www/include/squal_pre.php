<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require(getenv('SF_LOCAL_INC_PREFIX').'/etc/codex/conf/local.inc');
require($DOCUMENT_ROOT.'/include/database.php');
require($DOCUMENT_ROOT.'/include/session.php');
require($DOCUMENT_ROOT.'/include/user.php');
require($DOCUMENT_ROOT.'/include/utils.php');
require($DOCUMENT_ROOT.'/include/squal_exit.php');
require($DOCUMENT_ROOT.'/include/browser.php');

$sys_datefmt = "m/d/y H:i";

// #### Connect to db

db_connect();

if (!$conn) {
	exit_error("Could Not Connect to Database",db_error());
}

//require($DOCUMENT_ROOT.'/include/logger.php');

// #### set session

session_set();

?>
