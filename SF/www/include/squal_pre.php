<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require(getenv('SF_LOCAL_INC_PREFIX').'/etc/codex/conf/local.inc');
require_once('database.php');
require_once('session.php');
require_once('user.php');
require_once('utils.php');
require_once('squal_exit.php');
require_once('browser.php');

$sys_datefmt = "m/d/y H:i";

// #### Connect to db

db_connect();

if (!$conn) {
	exit_error("Could Not Connect to Database",db_error());
}

//require_once('logger.php');

// #### set session

session_set();

?>
