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

require_once('common/survey/SurveySingleton.class');

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

    echo "<P><B>";
    // Admin link is displayed only if the user is a project administrator
    if (user_ismember($group_id, 'A')) {
        echo"<A HREF=\"/survey/admin/?group_id=$group_id\">".$Language->getText('survey_s_utils','admin')."</A>";
    }

    if ($is_admin_page && $group_id && user_ismember($group_id, 'A')) {
	echo " | <A HREF=\"/survey/admin/add_survey.php?group_id=$group_id\">".$Language->getText('survey_admin_index','add_s')."</A>";
	echo " | <A HREF=\"/survey/admin/edit_survey.php?func=browse&group_id=$group_id\">".$Language->getText('survey_admin_browse_survey','edit_s')."</A>";
	echo " | <A HREF=\"/survey/admin/add_question.php?group_id=$group_id\">".$Language->getText('survey_admin_index','add_q')."</A>";
	echo " | <A HREF=\"/survey/admin/edit_question.php?func=browse&group_id=$group_id\">".$Language->getText('survey_admin_browse_question','edit_q')."</A>";
	echo " | <A HREF=\"/survey/admin/show_results.php?group_id=$group_id\">".$Language->getText('survey_s_utils','show_r')."</A>";
    }
    
    if (isset($params['help'])) {
        if (user_ismember($group_id, 'A')) {
            echo ' | ';
        }
        echo help_button($params['help'],false,$Language->getText('global','help'));
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

  $survey =& SurveySingleton::instance();
 
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
		<H3>'.$survey->getSurveyTitle(db_result($result, 0, 'survey_title')).'</H3>';
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

            $existing_response="";
            $response_exists=false;
            if (user_isloggedin()) {
                $sql2="SELECT * FROM survey_responses WHERE question_id='".$quest_array[$i]."' AND survey_id='".$survey_id."' AND user_id='".user_getid()."'";
                $result2=db_query($sql2);
                if (db_numrows($result) > 0) {
                    $existing_response=db_result($result2, 0, 'response');
                    $response_exists=true;
                }
            }
	    if ($question_type == $survey->COMMENT_ONLY) {
		/*
		  Don't show question number if it's just a comment
		  and show the comment as bold by default
		*/

		$return .= '
				<TR><TD VALIGN=TOP>&nbsp;</TD><TD>';

		$return .= '<b>'.util_unconvert_htmlspecialchars(stripslashes(db_result($result, 0, 'question'))).'</b><br>';

	    } else if ($question_type && $question_type != $survey->NONE){
		$return .= '
				<TR><TD VALIGN=TOP><B>';
		$return .= $q_num.'&nbsp;&nbsp;-&nbsp;&nbsp;</B></TD><TD>';
		$q_num++;
		$return .= util_unconvert_htmlspecialchars(stripslashes(db_result($result, 0, 'question'))).'<br>';
	    }

	    if ($question_type == $survey->RADIO_BUTTON_1_5) {
		/*
		  This is a r�dio-button question. Values 1-5.
		*/
		$return .= "<b>1</b>";
		for ($j=1; $j<=5; $j++) {
		    $return .= '
					<INPUT TYPE="RADIO" NAME="_'.$quest_array[$i].'" VALUE="'.$j.'"'.($existing_response==$j?" CHECKED ":"").'>';
		}
		$return .= "&nbsp;&nbsp;<b>5</b>";

	    } else if ($question_type == $survey->TEXT_AREA) {
		/*
		  This is a text-area question.
		*/
		$return .= '
				<textarea name="_'.$quest_array[$i].'" rows=5 cols=60 wrap="soft">'.($response_exists?$existing_response:"").'</textarea><br>';

	    } else if ($question_type == $survey->RADIO_BUTTON_YES_NO) {
		/*
		  This is a Yes/No question.
		*/
		$return .= '
				<b>'.$Language->getText('global','yes').'</b> <INPUT TYPE="RADIO" NAME="_'.$quest_array[$i].'" VALUE="1"'.($existing_response=="1"?" CHECKED ":"").'>';
		$return .= '&nbsp;&nbsp;';
		$return .= '
				 <b>'.$Language->getText('global','no').'</b><INPUT TYPE="RADIO" NAME="_'.$quest_array[$i].'" VALUE="5"'.($existing_response=="5"?" CHECKED ":"").'><br>';

	    } else if ($question_type == $survey->COMMENT_ONLY) {
		/*
		  This is a comment only.
		*/
		$return .= '
				<INPUT TYPE="HIDDEN" NAME="_'.$quest_array[$i].'" VALUE="-666">';

	    } else if ($question_type == $survey->TEXT_FIELD) {
		/*
		  This is a text-field question.
		*/
		$return .= '
				<INPUT TYPE="TEXT" name="_'.$quest_array[$i].'" SIZE=30 MAXLENGTH=100 '.($response_exists?" VALUE='".$existing_response."'":"").'><br>';

	    } else if ($question_type == $survey->RADIO_BUTTON) {
		/*
		  This is a radio-button question.
		*/
		
		$qry="SELECT * FROM survey_radio_choices WHERE question_id='$quest_array[$i]' ORDER BY choice_rank";
		$res=db_query($qry);
		$j=1;
		while ($row=db_fetch_array($res)) {
		    $value=$row['radio_choice'];
		    $return .= '
					<INPUT TYPE="RADIO" NAME="_'.$quest_array[$i].'" VALUE="'.$value.'"'.($existing_response==$value?" CHECKED ":"").'> '.$value.' <BR>';
		    $j++;
		}    
	    }	
	
	    if ($q_num > 1) {
	      $return .= '<br></TD></TR>';
	    }
	    $last_question_type=$question_type;
	}


	if ($q_num > 1) {
	  $return .= '
	<TR><TD ALIGN="center" COLSPAN="2">
	<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
	<BR>
	<A HREF="/survey/privacy.php?group_id='.$group_id.'&survey_id='.$survey_id.'">'.$Language->getText('survey_s_utils','privacy').'</A>';
	}

	$return .= '
	</TD></TR>
	</FORM>
	</TABLE>';

	if ($q_num == 1) {
	  $return .= $Language->getText('survey_admin_show_r_aggregate','no_active_question');
	}

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
    
    $survey =& SurveySingleton::instance();

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
	  '<TD>'.$survey->getSurveyTitle(db_result($result,$j,'survey_title'))."</TD>\n".
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
    $survey =& SurveySingleton::instance();
    
    $title_arr=array();
    $title_arr[]=$Language->getText('survey_index','s_id');
    $title_arr[]=$Language->getText('survey_s_utils','title');
    
    echo html_build_list_table_top ($title_arr);
    
    for ($j=0; $j<$rows; $j++)  {
	
	$survey_id = db_result($result,$j,'survey_id');
	echo '<tr class="'.html_get_alt_row_color($j).'">';
	
	echo "<TD><A HREF=\"/survey/admin/show_results_aggregate.php?group_id=$group_id&survey_id=$survey_id\">$survey_id</A></TD>".
	  '<TD width="90%">'.$survey->getSurveyTitle(db_result($result,$j,'survey_title'))."</TD>\n<tr>";
    }
    echo "</table>";
}


function  survey_utils_show_questions($result, $hlink_id=true, $show_delete=true) {
    global $group_id,$Language;

    $survey =& SurveySingleton::instance();
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
	$question_type_id = db_result($result,$j,'question_type_id');
	
	if ($question_type_id == 6) {
	    $warning='warning_loose_data';
	} else {
	    $warning='warning_loose_answers';
	}    
	
	echo "<tr class=\"". html_get_alt_row_color($j) ."\">\n";

	if ($hlink_id) {
	    echo "<TD><A HREF=\"/survey/admin/edit_question.php?func=update_question&group_id=$group_id&question_id=$question_id\">$question_id</A></TD>\n";
	} else {
	    echo "<TD>$question_id</TD>\n";
	}
	
	echo '<TD>'.db_result($result,$j,'question')."</TD>\n".
	  '<TD>'.$survey->getLabel($question_type_id)."</TD>\n";     
		
	if  ($show_delete) {
	    echo '<TD align=center>'.
		"<a href=\"/survey/admin/edit_question.php?func=delete_question&group_id=$group_id&question_id=$question_id\" ".
		'" onClick="return confirm(\''.$Language->getText('survey_s_utils',$warning).'\')">'.		
		'<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="'.$Language->getText('survey_s_utils','del_txt').'"></A></TD>';
	}

	echo "</tr>";
    }
    echo "</table>";
}

function  survey_utils_show_radio_list($result) {
    global $group_id,$question_id,$Language;

    $rows  =  db_numrows($result);
    
    $title_arr=array();
    $title_arr[]=$Language->getText('survey_s_utils','button_id');
    $title_arr[]=$Language->getText('survey_s_utils','text_r');
    $title_arr[]=$Language->getText('survey_s_utils','rank');
    $title_arr[]=$Language->getText('survey_s_utils','del');

    echo "<P><HR><P><H3>".$Language->getText('survey_admin_update_question','existing_r')."</H3>";
    echo "<h3>".$Language->getText('survey_s_utils','found',$rows)."</h3>";
    if ($rows) {
      echo $Language->getText('survey_admin_update_question','edit_r_msg'); 
      echo html_build_list_table_top ($title_arr);
    }

    for($j=0; $j<$rows; $j++)  {

	$choice_id = db_result($result, $j, 'choice_id');
	
	echo "<tr class=\"". html_get_alt_row_color($j) ."\">\n";
	echo "<TD><A HREF=\"/survey/admin/edit_question.php?func=update_radio&group_id=$group_id&question_id=$question_id&choice_id=$choice_id\">$choice_id</A></TD>\n".
	     '<TD>'.db_result($result,$j,'radio_choice')."</TD>\n".
	     '<TD>'.db_result($result,$j,'choice_rank')."</TD>\n".
	     '<TD align=center>'.
		"<a href=\"/survey/admin/edit_question.php?func=delete_radio&group_id=$group_id&question_id=$question_id&choice_id=$choice_id\">".
		'<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="'.$Language->getText('survey_s_utils','del_txt').'"></A></TD>';
	echo "</tr>";
    }

    if ($rows) {
      echo "</table>";  
    }
}

function survey_utils_show_radio_form($question_id, $choice_id) {
    global $group_id,$question_id,$Language;
    
    if ($choice_id != "") {
        // we are in case of update
	$sql = "SELECT * FROM survey_radio_choices WHERE question_id='$question_id' AND choice_id='$choice_id'";
        $res = db_query($sql);
        $answer_value = db_result($res,0,'radio_choice');
        $rank_value = db_result($res,0,'choice_rank');        
	$text_name = "choice";
	$rank_name = "ranking";
	$submit_name = "update_submit";	
	$submit_value=$Language->getText('survey_s_utils','update');
    } else {
        // we are in case of creation
	$answer_value = "";
	$rank_value = "";	
	$text_name = "answer";
	$rank_name = "rank";
	$submit_name = "create_submit";
        $submit_value=$Language->getText('survey_s_utils','create');
	$action = "/survey/admin/edit_question.php?func=create_radio&group_id=$group_id&question_id=$question_id";	
        echo "<hr>";
        echo '<h3>'.$Language->getText('survey_s_utils','add_button').'</h3>';
    }
    
    
    $return = '<TABLE><FORM ACTION="'.$action.'" METHOD="POST">    
    <INPUT TYPE="HIDDEN" NAME="question_id" VALUE="'.$question_id.'">
    <INPUT TYPE="HIDDEN" NAME="choice_id" VALUE="'.$choice_id.'">
    <TR><TD>'.$Language->getText('survey_s_utils','text_r').': <font color=red>*</font> </TD>
    <TD><INPUT TYPE="TEXT" NAME="'.$text_name.'" VALUE="'.$answer_value.'" SIZE=30></TD></TR>
    <TR><TD>'.$Language->getText('survey_s_utils','rank').': <font color=red>*</font> </TD>
    <TD><INPUT TYPE="TEXT" NAME="'.$rank_name.'" VALUE="'.$rank_value.'" SIZE=10></TD></TR></TABLE>    
    <p><INPUT TYPE="SUBMIT" NAME="'.$submit_name.'" VALUE="'.$submit_value.'"></p></FORM>
    <p><font color="red">*</font>: '.$Language->getText('survey_s_utils','required_fields').'</p>';
    
    echo $return;

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

// Check that the question exists
function survey_utils_question_exist($question_id) {
    $sql="SELECT * FROM survey_questions WHERE question_id='$question_id'";
    $result=db_query($sql);
    if (db_numrows($result) > 0) return true;
    else return false;
}


// Check that the question list only contains existing questions.
function survey_utils_all_questions_exist($survey_questions) {
    $temp_array=array();
    foreach (explode(",", $survey_questions) as $question) {
        if (!survey_utils_question_exist($question)) return false;
    }
    return true;
}

// Check that the question list only contains unique question numbers.
function survey_utils_unique_questions($survey_questions) {
    $temp_array=array();
    foreach (explode(",", $survey_questions) as $question) {
        if (in_array($question,$temp_array)) {
            return false;
        }
        $temp_array[]=$question;
    }
    return true;
}

?>
