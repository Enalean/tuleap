<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require('../survey/survey_utils.php');


survey_header(array('title'=>'Survey'));

echo $Language->getText('survey_privacy','txt',$GLOBALS['sys_name']);


survey_footer(array());

?>
