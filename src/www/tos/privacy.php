<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
require_once __DIR__ . '/../include/pre.php';    // Initial db and session library, opens session
$HTML->header(\Tuleap\Layout\HeaderConfiguration::fromTitle("Privacy Policy"));

include($Language->getContent('tos/privacy'));

$HTML->footer([]);
