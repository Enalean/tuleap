<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require('../survey/survey_utils.php');

$Language->loadLanguageMsg('survey/survey');

survey_header(array('title'=>$Language->getText('survey_s','s'),
		    'help'=>'SurveyManager.html#PublishingaSurvey'));

if (!$survey_id || !$group_id) {
	echo "<H1>".$Language->getText('survey_s','g_id_err')."</H1>";
} else {

    // select this survey from the database
    $sql="select * from surveys where survey_id='$survey_id'";
    $result=db_query($sql);

    if (!user_isloggedin() && !db_result($result, 0, "is_anonymous")) {
	/*
		Tell them they need to be logged in
	*/
	echo $Language->getText('survey_s','log_in','/account/login.php?return_to='.urlencode($REQUEST_URI));
	survey_footer(array());
	exit;
    } else {
	survey_utils_show_survey($group_id,$survey_id);
    }
}

survey_footer(array());

?>
