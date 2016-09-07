<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// Copyright (c) Enalean, 2015. All Rights Reserved.
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('common/survey/SurveySingleton.class.php');
require_once('../survey/survey_utils.php');

if (! ForgeConfig::get('sys_use_surveys')) {
    exit_permission_denied();
}

survey_header(array('title'=>$Language->getText('survey_index','s'),
		    'help'=>'survey.html'));

if (!$group_id) {
	echo "<H1>".$Language->getText('survey_index','g_id_err')."</H1>";
}

function  ShowResultsGroupSurveys($result) {
	global $group_id,$Language;

	$survey   = SurveySingleton::instance();
        $purifier = Codendi_HTMLPurifier::instance();

	$rows  =  db_numrows($result);
	$cols  =  db_numfields($result);

	$title_arr=array();
	$title_arr[]=$Language->getText('survey_index','s_id');
	$title_arr[]=$Language->getText('survey_index','s_tit');

	echo html_build_list_table_top ($title_arr);

	for($j=0; $j<$rows; $j++)  {

		echo "<tr class=\"". html_get_alt_row_color($j) ."\">\n";

		echo "<TD><A HREF=\"survey.php?group_id=$group_id&survey_id=".db_result($result,$j,"survey_id")."\">".
			db_result($result,$j,"survey_id")."</TD>";

		printf("<TD>%s</TD>\n", $purifier->purify($survey->getSurveyTitle(db_result($result,$j,'survey_title'))));

		echo "</tr>";
	}
	echo "</table>"; //</TD></TR></TABLE>");
}

$sql="SELECT survey_id,survey_title FROM surveys WHERE group_id='" . db_ei($group_id) . "' AND is_active='1'";

$result=db_query($sql);

if (!$result || db_numrows($result) < 1) {
	echo "<H2>".$Language->getText('survey_index','no_act_s')."</H2>";
	echo db_error();
} else {
	$pm       = ProjectManager::instance();
        $purifier = Codendi_HTMLPurifier::instance();
    echo "<H2>".$Language->getText('survey_index','s_for', $purifier->purify($pm->getProject($group_id)->getPublicName()))."</H2>";
	ShowResultsGroupSurveys($result);
}

survey_footer(array());
