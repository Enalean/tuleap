<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
require_once __DIR__ . '/../include/pre.php';


$HTML->header(array('title' => $Language->getText('account_suspended', 'title')));

echo '<P>' . $Language->getText('account_suspended', 'message', array($GLOBALS['sys_email_contact']));

echo $HTML->footer(array());
