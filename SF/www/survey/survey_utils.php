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
	Heavily refactored by Laurent Julliard, 2002
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
	echo " | <A HREF=\"/survey/admin/edit_survey.php?func=browse&group_id=$group_id\">Edit Surveys</A>";
	echo " | <A HREF=\"/survey/admin/add_question.php?group_id=$group_id\">Add Questions</A>";
	echo " | <A HREF=\"/survey/admin/edit_question.php?func=browse&group_id=$group_id\">Edit Questions</A>";
	echo " | <A HREF=\"/survey/admin/show_results.php?group_id=$group_id\">Show Results</A>";
    }
    
    if ($params['help']) {
	echo ' | '.help_button($params['help'],false,'Help');
    }

    echo "</B><P>";

}

function survey_footer($params) {
    site_project_footer($params);
}

function  survey_utils_show_surveys($result, $show_delete=true) {
    global $group_id;
    $rows  =  db_numrows($result);
    
    $title_arr=array();
    $title_arr[]='Survey ID';
    $title_arr[]='Title';
    $title_arr[]='Questions';
    $title_arr[]='Active';
    $title_arr[]='Anonymous';
    if ($show_delete) { $title_arr[]='Delete?'; }
    
    echo html_build_list_table_top ($title_arr);
    
    for ($j=0; $j<$rows; $j++)  {
	
	$survey_id = db_result($result,$j,'survey_id');
	echo '<tr BGCOLOR="'.html_get_alt_row_color($j).'">';
	
	echo "<TD><A HREF=\"/survey/admin/edit_survey.php?func=update_survey&group_id=$group_id&survey_id=$survey_id\">$survey_id</A></TD>".
	    '<TD>'.db_result($result,$j,'survey_title')."</TD>\n".
	    '<TD>'.str_replace(',',', ',db_result($result,$j,'survey_questions'))."</TD>\n";     

	html_display_boolean(db_result($result,$j,'is_active'),"<TD align=center>Yes</TD>","<TD align=center>No</TD>");
	html_display_boolean(db_result($result,$j,'is_anonymous'),"<TD align=center>Yes</TD>","<TD align=center>No</TD>");
        
	if ($show_delete) {
	    echo '<TD align=center>'.
		"<a href=\"/survey/admin/edit_survey.php?func=delete_survey&group_id=$group_id&survey_id=$survey_id\" ".
		'" onClick="return confirm(\'Delete this survey?\n\n** Important **\nIf you delete a survey, all associated responses will be lost.\')">'.
		'<IMG SRC="/images/ic/trash.png" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE"></A></TD>';
	}
	echo "</tr>";
    }
    echo "</table>";

}

function  survey_utils_show_surveys_for_results($result) {
    global $group_id;
    $rows  =  db_numrows($result);
    
    $title_arr=array();
    $title_arr[]='Survey ID';
    $title_arr[]='Title';
    
    echo html_build_list_table_top ($title_arr);
    
    for ($j=0; $j<$rows; $j++)  {
	
	$survey_id = db_result($result,$j,'survey_id');
	echo '<tr BGCOLOR="'.html_get_alt_row_color($j).'">';
	
	echo "<TD><A HREF=\"/survey/admin/show_results_aggregate.php?group_id=$group_id&survey_id=$survey_id\">$survey_id</A></TD>".
	    '<TD width="90%">'.db_result($result,$j,'survey_title')."</TD>\n<tr>";
    }
    echo "</table>";
}


function  survey_utils_show_questions($result, $show_delete=true) {
    global $group_id;

    $rows  =  db_numrows($result);

    echo "<h3>$rows Found</h3>";

    $title_arr=array();
    $title_arr[]='Question ID';
    $title_arr[]='Question';
    $title_arr[]='Type';
    if ($show_delete) { $title_arr[]='Delete?'; }

    echo html_build_list_table_top ($title_arr);

    for($j=0; $j<$rows; $j++)  {

	$question_id = db_result($result,$j,'question_id');
	echo "<tr BGCOLOR=\"". html_get_alt_row_color($j) ."\">\n";

	echo "<TD><A HREF=\"/survey/admin/edit_question.php?func=update_question&group_id=$group_id&question_id=$question_id\">$question_id</A></TD>\n".
	    '<TD>'.db_result($result,$j,'question')."</TD>\n".
	    '<TD>'.db_result($result,$j,'question_type')."</TD>\n";     
		
	if  ($show_delete) {
	    echo '<TD align=center>'.
		"<a href=\"/survey/admin/edit_question.php?func=delete_question&group_id=$group_id&question_id=$question_id\" ".
		'" onClick="return confirm(\'Delete this question?\n\n** Important **\nIf you delete a question, all associated responses in all surveys using this question will be lost.\')">'.
		'<IMG SRC="/images/ic/trash.png" HEIGHT="16" WIDTH="16" BORDER="0" ALT="DELETE"></A></TD>';
	}

	echo "</tr>";
    }
    echo "</table>";
}

function  survey_utils_show_comments($result) {

    $rows  =  db_numrows($result);

    $title_arr=array();
    $title_arr[]='Response';
    $title_arr[]='# of occurences';
    
    $sum = 0;
    for($j=0; $j<$rows; $j++)  {

	$count = db_result($result,$j,'count');
	$resp = db_result($result,$j,'response');

	if ($resp == '') { $resp = '- BLANK RESPONSES -'; }
	
	$out .= '<tr BGCOLOR="'.html_get_alt_row_color($j).'">';
	
	$out .= "<TD width=\"90%\">$resp</TD>\n".
	    '<TD >'.$count."</TD><tr>\n";
	$sum += $count;
    }
    
    echo "<h4>Total number of answers: $sum</h4>\n";
    echo html_build_list_table_top ($title_arr);
    echo $out;
    echo "</table>";
}

?>
