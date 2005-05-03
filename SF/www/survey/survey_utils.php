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

$Language->loadLanguageMsg('survey/survey');

function survey_header($params) {
    global $group_id,$is_admin_page,$Language;

    $params['toptab']='survey';
    $params['group']=$group_id;

    $project=project_get_object($group_id);

    if (!$project->usesSurvey()) {
	exit_error($Language->getText('global','error'),$Language->getText('survey_s_utils','s_off'));
    }

    site_project_header($params);

    echo "<P><B><A HREF=\"/survey/admin/?group_id=$group_id\">".$Language->getText('survey_s_utils','admin')."</A>";

    if ($is_admin_page && $group_id) {
	echo " | <A HREF=\"/survey/admin/add_survey.php?group_id=$group_id\">".$Language->getText('survey_admin_index','add_s')."</A>";
	echo " | <A HREF=\"/survey/admin/edit_survey.php?func=browse&group_id=$group_id\">".$Language->getText('survey_admin_browse_survey','edit_s')."</A>";
	echo " | <A HREF=\"/survey/admin/add_question.php?group_id=$group_id\">".$Language->getText('survey_admin_index','add_q')."</A>";
	echo " | <A HREF=\"/survey/admin/edit_question.php?func=browse&group_id=$group_id\">".$Language->getText('survey_admin_browse_question','edit_q')."</A>";
	echo " | <A HREF=\"/survey/admin/show_results.php?group_id=$group_id\">".$Language->getText('survey_s_utils','show_r')."</A>";
    }
    
    if ($params['help']) {
	echo ' | '.help_button($params['help'],false,$Language->getText('global','help'));
    }

    echo "</B><P>";

}

function survey_footer($params) {
    site_project_footer($params);
}

/*

	Select and show a specific survey from the database
        WARNING: the method does not check that the survey belongs to 
        the given group.

*/

function survey_utils_show_survey ($group_id,$survey_id,$echoout=1) {
  global $Language;

    $return = '<FORM ACTION="/survey/survey_resp.php" METHOD="POST">
 <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
 <INPUT TYPE="HIDDEN" NAME="survey_id" VALUE="'.$survey_id.'">';

    /*
      Select this survey from the database
    */

    $sql="SELECT * FROM surveys WHERE survey_id='$survey_id'";

    $result=db_query($sql);

    if (db_numrows($result) > 0) {
	$return .= '
		<H3>'.db_result($result, 0, 'survey_title').'</H3>';
	/*
	  Select the questions for this survey
	*/

	$questions=db_result($result, 0, 'survey_questions');
	$quest_array=explode(',', $questions);
	$count=count($quest_array);
	$return .= '
		<TABLE BORDER=0>';
	$q_num=1;

	for ($i=0; $i<$count; $i++) {
	    /*
	      Build the questions on the HTML form
	    */

	    $sql="SELECT * FROM survey_questions WHERE question_id='".$quest_array[$i]."'";
	    $result=db_query($sql);
	    $question_type=db_result($result, 0, 'question_type');

	    if ($question_type == '4') {
		/*
		  Don't show question number if it's just a comment
		  and show the comment as bold by default
		*/

		$return .= '
				<TR><TD VALIGN=TOP>&nbsp;</TD><TD>';

		$return .= '<b>'.util_unconvert_htmlspecialchars(stripslashes(db_result($result, 0, 'question'))).'</b><br>';

	    } else {
		$return .= '
				<TR><TD VALIGN=TOP><B>';
		$return .= $q_num.'&nbsp;&nbsp;-&nbsp;&nbsp;</B></TD><TD>';
		$q_num++;
		$return .= util_unconvert_htmlspecialchars(stripslashes(db_result($result, 0, 'question'))).'<br>';
	    }

	    if ($question_type == "1") {
		/*
		  This is a rædio-button question. Values 1-5.
		*/
		$return .= "<b>1</b>";
		for ($j=1; $j<=5; $j++) {
		    $return .= '
					<INPUT TYPE="RADIO" NAME="_'.$quest_array[$i].'" VALUE="'.$j.'">';
		}
		$return .= "&nbsp;&nbsp;<b>5</b>";

	    } else if ($question_type == '2') {
		/*
		  This is a text-area question.
		*/
		$return .= '
				<textarea name="_'.$quest_array[$i].'" rows=5 cols=60 wrap="soft"></textarea>';

	    } else if ($question_type == '3') {
		/*
		  This is a Yes/No question.
		*/
		$return .= '
				<b>'.$Language->getText('global','yes').'</b> <INPUT TYPE="RADIO" NAME="_'.$quest_array[$i].'" VALUE="1">';
		$return .= '&nbsp;&nbsp;';
		$return .= '
				 <b>'.$Language->getText('global','no').'</b><INPUT TYPE="RADIO" NAME="_'.$quest_array[$i].'" VALUE="5">';

	    } else if ($question_type == '4') {
		/*
		  This is a comment only.
		*/
		$return .= '
				<INPUT TYPE="HIDDEN" NAME="_'.$quest_array[$i].'" VALUE="-666">';

	    } else if ($question_type == '5') {
		/*
		  This is a text-field question.
		*/
		$return .= '
				<INPUT TYPE="TEXT" name="_'.$quest_array[$i].'" SIZE=30 MAXLENGTH=100>';

	    }
	    $return .= '</TD></TR>';

	    $last_question_type=$question_type;
	}

	$return .= '
	<TR><TD ALIGN="center" COLSPAN="2">

	<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
	<BR>
	<A HREF="/survey/privacy.php?group_id='.$group_id.'&survey_id='.$survey_id.'">'.$Language->getText('survey_s_utils','privacy').'</A>
	</TD></TR>
	</FORM>
	</TABLE>';

    } else {
	$return .= "<H3>".$Language->getText('survey_s_utils','not_found')."</H3>";
    }

    if ( $echoout ) {
	echo $return;
    } else {
	return $return;
    }

}

function  survey_utils_show_surveys($result, $show_delete=true) {
    global $group_id,$Language;
    $rows  =  db_numrows($result);
    
    $title_arr=array();
    $title_arr[]=$Language->getText('survey_index','s_id');
    $title_arr[]=$Language->getText('survey_s_utils','title');
    $title_arr[]=$Language->getText('survey_admin_update_survey','q');
    $title_arr[]=$Language->getText('survey_s_utils','active');
    $title_arr[]=$Language->getText('survey_s_utils','anon');
    if ($show_delete) { $title_arr[]=$Language->getText('survey_s_utils','del'); }
    
    echo html_build_list_table_top ($title_arr);
    
    for ($j=0; $j<$rows; $j++)  {
	
	$survey_id = db_result($result,$j,'survey_id');
	echo '<tr class="'.html_get_alt_row_color($j).'">';
	
	echo "<TD><A HREF=\"/survey/admin/edit_survey.php?func=update_survey&group_id=$group_id&survey_id=$survey_id\">$survey_id</A></TD>".
	    '<TD>'.db_result($result,$j,'survey_title')."</TD>\n".
	    '<TD>'.str_replace(',',', ',db_result($result,$j,'survey_questions'))."</TD>\n";     

	html_display_boolean(db_result($result,$j,'is_active'),"<TD align=center>".$Language->getText('global','yes')."</TD>","<TD align=center>".$Language->getText('global','no')."</TD>");
	html_display_boolean(db_result($result,$j,'is_anonymous'),"<TD align=center>".$Language->getText('global','yes')."</TD>","<TD align=center>".$Language->getText('global','no')."</TD>");
        
	if ($show_delete) {
	    echo '<TD align=center>'.
		"<a href=\"/survey/admin/edit_survey.php?func=delete_survey&group_id=$group_id&survey_id=$survey_id\" ".
		'" onClick="return confirm(\''.$Language->getText('survey_s_utils','del_s').'\')">'.
		'<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="'.$Language->getText('survey_s_utils','del_txt').'"></A></TD>';
	}
	echo "</tr>";
    }
    echo "</table>";

}

function  survey_utils_show_surveys_for_results($result) {
    global $group_id,$Language;
    $rows  =  db_numrows($result);
    
    $title_arr=array();
    $title_arr[]=$Language->getText('survey_index','s_id');
    $title_arr[]=$Language->getText('survey_s_utils','title');
    
    echo html_build_list_table_top ($title_arr);
    
    for ($j=0; $j<$rows; $j++)  {
	
	$survey_id = db_result($result,$j,'survey_id');
	echo '<tr class="'.html_get_alt_row_color($j).'">';
	
	echo "<TD><A HREF=\"/survey/admin/show_results_aggregate.php?group_id=$group_id&survey_id=$survey_id\">$survey_id</A></TD>".
	    '<TD width="90%">'.db_result($result,$j,'survey_title')."</TD>\n<tr>";
    }
    echo "</table>";
}


function  survey_utils_show_questions($result, $show_delete=true) {
    global $group_id,$Language;

    $rows  =  db_numrows($result);

    echo "<h3>".$Language->getText('survey_s_utils','found',$rows)."</h3>";

    $title_arr=array();
    $title_arr[]=$Language->getText('survey_s_utils','q_id');
    $title_arr[]=$Language->getText('survey_admin_update_question','q');
    $title_arr[]=$Language->getText('survey_admin_show_r_aggregate','type');
    if ($show_delete) { $title_arr[]=$Language->getText('survey_s_utils','del'); }

    echo html_build_list_table_top ($title_arr);

    for($j=0; $j<$rows; $j++)  {

	$question_id = db_result($result,$j,'question_id');
	echo "<tr class=\"". html_get_alt_row_color($j) ."\">\n";

	echo "<TD><A HREF=\"/survey/admin/edit_question.php?func=update_question&group_id=$group_id&question_id=$question_id\">$question_id</A></TD>\n".
	    '<TD>'.db_result($result,$j,'question')."</TD>\n".
	    '<TD>'.db_result($result,$j,'question_type')."</TD>\n";     
		
	if  ($show_delete) {
	    echo '<TD align=center>'.
		"<a href=\"/survey/admin/edit_question.php?func=delete_question&group_id=$group_id&question_id=$question_id\" ".
		'" onClick="return confirm(\''.$Language->getText('survey_s_utils','del_q').'\')">'.
		'<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="'.$Language->getText('survey_s_utils','del_txt').'"></A></TD>';
	}

	echo "</tr>";
    }
    echo "</table>";
}

function  survey_utils_show_comments($result) {
  global $Language;

    $rows  =  db_numrows($result);

    $title_arr=array();
    $title_arr[]=$Language->getText('survey_s_utils','resp');
    $title_arr[]=$Language->getText('survey_s_utils','occ');
    
    $sum = 0;
    for($j=0; $j<$rows; $j++)  {

	$count = db_result($result,$j,'count');
	$resp = db_result($result,$j,'response');

	if ($resp == '') { $resp = $Language->getText('survey_s_utils','blank'); }
	
	$out .= '<tr class="'.html_get_alt_row_color($j).'">';
	
	$out .= "<TD width=\"90%\">$resp</TD>\n".
	    '<TD >'.$count."</TD><tr>\n";
	$sum += $count;
    }
    
    echo "<h4>".$Language->getText('survey_s_utils','total_no',$sum)."</h4>\n";
    echo html_build_list_table_top ($title_arr);
    echo $out;
    echo "</table>";
}

// Take a list of question numbers as input and make sure there
// isn't any space in the list or commas at the beginning or at the
// end
function  survey_utils_cleanup_questions($question_list) {

    $question_list = preg_replace("/\s/","",$question_list);
    $question_list = preg_replace("/^,+/","",$question_list);
    $question_list = preg_replace("/,+$/","",$question_list);
    
    return $question_list;
}

?>
