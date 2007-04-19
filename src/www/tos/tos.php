<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: tos.php 4850 2007-02-06 17:25:28 +0000 (Tue, 06 Feb 2007) nterray $

require_once('pre.php');  // Initial db and session library, opens session

$HTML->header( array( 'title' => "Terms of Service Agreement" ) );

include($Language->getContent('project/tos'));

$HTML->footer( array() );

?>

