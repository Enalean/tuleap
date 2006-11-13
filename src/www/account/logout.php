<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    

if (isset($session_hash)) {
    session_delete($session_hash);
}
session_cookie('session_hash','');
session_redirect('/');

?>
