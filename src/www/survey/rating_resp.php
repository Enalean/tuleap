<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');

$Language->loadLanguageMsg('survey/survey');

$HTML->header(array('title'=>$Language->getText('survey_rating_resp','voting')));

if (!user_isloggedin()) {
	echo "<H2>".$Language->getText('survey_rating_resp','log_in')."</H2>";
} else {
	if ($vote_on_id && $response && $flag) {
		/*
			$flag
			1=project
			2=release
		*/

		$sql="DELETE FROM survey_rating_response WHERE user_id='".user_getid()."' AND type='$flag' AND id='$vote_on_id'";
		$toss=db_query($sql);

		$sql="INSERT INTO survey_rating_response (user_id,type,id,response,date) ".
			"VALUES ('".user_getid()."','$flag','$vote_on_id','$response','".time()."')";
		$result=db_query($sql);
		if (!$result) {
			$feedback .= " ".$Language->getText('global','error')". ";
			echo "<H1>".$Language->getText('survey_rating_resp','ins_err')."</H1>";
			echo db_error();
		} else {
			$feedback .= " ".$Language->getText('survey_rating_resp','vote_reg')." ";
			echo "<H2>".$Language->getText('survey_rating_resp','vote_reg')."</H2>";
			echo "<A HREF=\"javascript:history.back()\"><B>".$Language->getText('survey_rating_resp','revote');
		}
	} else {
		echo "<H1>".$Language->getText('survey_rating_resp','missing_param')."</H1>";
	}
}
$HTML->footer(array());
?>
