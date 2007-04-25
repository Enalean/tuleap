<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('common/survey/SurveySingleton.class.php');
require('../survey/survey_utils.php');

$Language->loadLanguageMsg('survey/survey');

survey_header(array('title'=>$Language->getText('survey_index','s'),
		    'help'=>'SurveyManager.html'));

if (!$group_id) {
	echo "<H1>".$Language->getText('survey_index','g_id_err')."</H1>";
}

function  ShowResultsGroupSurveys($result) {
	global $group_id,$Language;

	$survey =& SurveySingleton::instance();

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

		printf("<TD>%s</TD>\n",$survey->getSurveyTitle(db_result($result,$j,'survey_title')));

		echo "</tr>";
	}
	echo "</table>"; //</TD></TR></TABLE>");
}

$sql="SELECT survey_id,survey_title FROM surveys WHERE group_id='$group_id' AND is_active='1'";

$result=db_query($sql);

if (!$result || db_numrows($result) < 1) {
	echo "<H2>".$Language->getText('survey_index','no_act_s')."</H2>";
	echo db_error();
} else {
	echo "<H2>".$Language->getText('survey_index','s_for',group_getname($group_id))."</H2>";
	ShowResultsGroupSurveys($result);
}

survey_footer(array());

?>
