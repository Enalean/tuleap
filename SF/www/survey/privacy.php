<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require('../survey/survey_utils.php');

$LANG->loadLanguageMsg('survey/survey');

survey_header(array('title'=>'Survey'));

echo $LANG->getText('survey_privacy','txt',$GLOBALS['sys_name']);


survey_footer(array());

?>
