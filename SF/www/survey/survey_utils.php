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

	echo "<P><B><A HREF=\"/survey/admin/?group_id=$group_id\">Admin</A></B>";

	if ($is_admin_page && $group_id) {
		echo " | <A HREF=\"/survey/admin/add_survey.php?group_id=$group_id\">Add Surveys</A>";
		echo " | <A HREF=\"/survey/admin/edit_survey.php?func=browse&group_id=$group_id\">Edit Surveys</A>";
		echo " | <A HREF=\"/survey/admin/add_question.php?group_id=$group_id\">Add Questions</A>";
		echo " | <A HREF=\"/survey/admin/show_questions.php?group_id=$group_id\">Edit Questions</A>";
		echo " | <A HREF=\"/survey/admin/show_results.php?group_id=$group_id\">Show Results</A></B>";
	}

	echo "<P>";

}

function survey_footer($params) {
	site_project_footer($params);
}

function survey_delete($group_id,$survey_id) {

    global $feedback;

    $feedback = '';
    // Delete first the data associate to the survey if any
    $res = db_query("DELETE FROM survey_responses WHERE group_id=$group_id AND survey_id=$survey_id");
    // Then delete the survey itself
    $res = db_query("DELETE FROM surveys WHERE survey_id=$survey_id");
    if (db_affected_rows($res) <= 0) {
	    $feedback .= "Error deleting survey #$survey_id: ".db_error($res);
    } else {
	    $feedback .= "Survey successfully deleted";
    }    
}

function survey_update($group_id,$survey_id,$survey_title,$survey_questions,$is_active,$is_anonymous) {
    
    global $feedback;
    
    $feedback = '';
	$sql="UPDATE surveys SET survey_title='$survey_title', survey_questions='$survey_questions', is_active='$is_active', is_anonymous='$is_anonymous' ".
		"WHERE survey_id='$survey_id' AND group_id='$group_id'";
	$result=db_query($sql);
	if (db_affected_rows($result) < 1) {
		$feedback .= ' UPDATE FAILED - '.db_error();
	} else {
		$feedback .= ' UPDATE SUCCESSFUL ';
	}
}

function  ShowResultsEditSurvey($result) {
	global $group_id,$PHP_SELF;
	$rows  =  db_numrows($result);
	$cols  =  db_numfields($result);
	echo "<h3>$rows Found</h3>";

	$title_arr=array();
	$title_arr[]='Survey ID';
	$title_arr[]='Group ID';
	$title_arr[]='Title';
	$title_arr[]='Questions';
	$title_arr[]='Active';
	$title_arr[]='Anonymous';
	$title_arr[]='Delete?';

	echo html_build_list_table_top ($title_arr);

	for ($j=0; $j<$rows; $j++)  {

		echo '<tr BGCOLOR="'.html_get_alt_row_color($j).'">';

		echo "<TD><A HREF=\"/survey/admin/edit_survey.php?func=update_survey&group_id=$group_id&survey_id=".
			db_result($result,$j,0)."\">".db_result($result,$j,0)."</A></TD>";
		for ($i = 1; $i < $cols-3; $i++)  {
			printf("<TD>%s</TD>\n",db_result($result,$j,$i));
		}
		$questions = explode(",",db_result($result,$j,3));
		echo "<TD>";
        for($k=0;$k<count($questions)-1;$k++) {
            echo $questions[$k].", ";
        }
        echo $questions[$k];
		echo "</TD>";

        html_display_boolean(db_result($result,$j,4),"<TD align=center>Yes</TD>","<TD align=center>No</TD>");
        html_display_boolean(db_result($result,$j,5),"<TD align=center>Yes</TD>","<TD align=center>No</TD>");
        
        echo '<TD align=center>'.
			 "<a href=\"/survey/admin/edit_survey.php?func=delete_survey&group_id=$group_id&survey_id=".db_result($result,$j,0)."\" ".
			 '" onClick="return confirm(\'Delete this survey?\n\n** Important **\nIf you delete a survey, all associated responses will be lost.\')">'.
			 '<IMG SRC="/images/ic/trash.png" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE"></A></TD>';
		echo "</tr>";
	}
	echo "</table>";

}

?>

