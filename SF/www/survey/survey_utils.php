<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*
	Survey System
	By Tim Perdue, Sourceforge, 11/99
*/

function survey_header($params) {
	global $group_id,$is_admin_page,$DOCUMENT_ROOT;

	$params['toptab']='surveys';
	$params['group']=$group_id;

	$project=project_get_object($group_id);

	if (!$project->usesSurvey()) {
		exit_error('Error','This Group Has Turned Off Surveys');
	}

	site_project_header($params);

	echo "<P><B><A HREF=\"/survey/admin/?group_id=$group_id\">Admin</A>";

	if ($is_admin_page && $group_id) {
		echo " | <A HREF=\"/survey/admin/add_survey.php?group_id=$group_id\">Add Surveys</A>";
		echo " | <A HREF=\"/survey/admin/edit_survey.php?group_id=$group_id\">Edit Surveys</A>";
		echo " | <A HREF=\"/survey/admin/add_question.php?group_id=$group_id\">Add Questions</A>";
		echo " | <A HREF=\"/survey/admin/show_questions.php?group_id=$group_id\">Edit Questions</A>";
		echo " | <A HREF=\"/survey/admin/show_results.php?group_id=$group_id\">Show Results</A></B>";
	}

	echo "<P>";

}

function survey_footer($params) {
	site_project_footer($params);
}

?>
