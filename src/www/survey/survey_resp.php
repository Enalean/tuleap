<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// Copyright (c) Enalean, 2015. All Rights Reserved.
// http://sourceforge.net
//
// 

require_once('pre.php');
require('../survey/survey_utils.php');

if (! ForgeConfig::get('sys_use_surveys')) {
	exit_permission_denied();
}

survey_header(array('title'=>$Language->getText('survey_s_resp','s_compl')));

// select this survey from the database
$sql="select * from surveys where survey_id='" . db_ei($survey_id) . "'";
$result_survey=db_query($sql);

if (!$survey_id || !$group_id) {
	/*
		Quit if params are not provided
	*/
	echo "<H1>".$Language->getText('survey_index','g_id_err')."</H1>";
	survey_footer(array());
	exit;
}

if (!user_isloggedin() && !db_result($result_survey, 0, "is_anonymous")) {
	/*
		Tell them they need to be logged in
	*/
	echo $Language->getText('survey_s','log_in','/account/login.php?return_to='.urlencode($REQUEST_URI));
	survey_footer(array());
	exit;
}

?>

<H2><?php echo $Language->getText('survey_s_resp','s_compl'); ?></H2><P>

<?php echo $Language->getText('survey_s_resp','thanks'); ?>
<P>
<?php
/*
	Delete this customer's responses in case they had back-arrowed
	Only do that for non anonymous users because if anonymous
	responses are allowed then they all have user_id = 0 and we don't
	want to delete them.
*/
if (user_isloggedin()) {
    $result=db_query("DELETE FROM survey_responses WHERE survey_id='" . db_ei($survey_id) . "' "
            . "AND group_id='" . db_ei($group_id) . "' AND user_id='" . db_ei(user_getid()) . "'");
}


/*
	Select the questions for this survey
*/

$quest_array=explode(',', db_result($result_survey, 0, "survey_questions"));

$count=count($quest_array);
$now=time();

for ($i=0; $i<$count; $i++) {

	/*
		Insert each form value into the responses table
	*/

	$val="_$quest_array[$i]";

	$sql="INSERT INTO survey_responses (user_id,group_id,survey_id,question_id,response,date) ".
		"VALUES ('" . db_ei(user_getid()) . "','" . db_ei($group_id) . "','" . db_ei($survey_id) . "',"
                . "'" . db_ei($quest_array[$i]) . "','" . db_es($$val) . "','" . db_ei($now) ."')";
	$result=db_query($sql);
	if (!$result) {
		echo "<h1>".$Language->getText('global','error')."</h1>";
	}
}

survey_footer(array());
