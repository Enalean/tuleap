<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
require_once __DIR__ . '/../include/pre.php';  // Initial db and session library, opens session

$HTML->header(array( 'title' => "Terms of Service Agreement" ));

include($Language->getContent('project/tos'));

$HTML->footer(array());
