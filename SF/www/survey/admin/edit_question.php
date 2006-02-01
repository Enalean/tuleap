<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../survey_data.php');
require('../survey_utils.php');

$Language->loadLanguageMsg('survey/survey');

$is_admin_page='y';

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo '<H1>'.$Language->getText('survey_admin_add_question','perm_denied').'</H1>';
	survey_footer(array());
	exit;
}

switch ($func) {

 case 'browse' :
     require('./browse_question.php');
     break;

 case 'delete_question':
     survey_data_question_delete($group_id,$question_id);
     require('./browse_question.php');
     break;

 case 'update_question':
     if ($post_changes) {	
	 // recuperate the old question type before update
	 $qry = "SELECT * FROM survey_questions WHERE group_id='$group_id' AND question_id='$question_id'";
	 $res = db_query($qry);
	 $old_quest_type = db_result($res,0,'question_type');
	 	 
	 // Update the question
	 survey_data_question_update($group_id, $question_id, 
		 htmlspecialchars($question), $question_type);
	 
	 // Delete radio buttons if the question type changes from radio-button	to anything else different
	 if (($old_quest_type=="6") && ($question_type != "6")) {
	     $sql = "SELECT * FROM survey_radio_choices WHERE question_id='$question_id'";
	     $result = db_query($sql);
	     $rows = db_numrows($result);
	     if ($rows > 0) {
	         for ($j=0; $j<$rows; $j++) {
		     $radio_id=db_result($result,$j,'choice_id');
	             survey_data_radio_delete($question_id,$radio_id);
		 }
	     }	 
	 }	 
	
	 if (($old_quest_type != "6") && ($question_type=="6")) {	     
	     // display the radio-buttons list and form in case type is changed to radio
	     session_redirect("/survey/admin/edit_question.php?func=update_question&group_id=$group_id&question_id=$question_id");
	 } else {
	     require('./browse_question.php');
	 }	 
     } else {
	 // Show the form to update the question
	 require('./update_question.php');
     }
    break;
    
 case 'update_radio':
     if ($GLOBALS['update_submit']) {
         if ($GLOBALS['choice'] == "") {
	     $feedback .= " ".$Language->getText('survey_admin_update_radio','fill_r_text');
	     require('./update_radio.php');
	 } else if ($GLOBALS['ranking'] == "") {
	     $feedback .= " ".$Language->getText('survey_admin_update_radio','fill_r_rank');
	     require('./update_radio.php');
	 } else if (! is_numeric($GLOBALS['ranking'])) {
	     $feedback .= " ".$Language->getText('survey_s_data','r_rank_int');
	     require('./update_radio.php');
	 } else {	 
             // achieve the update, then return to 'Edit A Question' page
             survey_data_radio_update($question_id,$choice_id,$choice,$ranking);
             require('./update_question.php');	    
	 }
     } else {
	 // show the form to update the radio
	 require('./update_radio.php');
     }
     break;    
    
 case 'create_radio':
    if ($GLOBALS['create_submit']) {
        if ($GLOBALS['answer'] == "") {
	    $feedback .= " ".$Language->getText('survey_admin_update_radio','fill_r_text');
	} else if ($GLOBALS['rank'] == "") {
	    $feedback .= " ".$Language->getText('survey_admin_update_radio','fill_r_rank');
	} else if (! is_numeric($GLOBALS['rank'])) {
	    $feedback .= " ".$Language->getText('survey_s_data','r_rank_int');
	} else {
	    // achieve the creation
            survey_data_radio_create($question_id,$answer,$rank);
	}
    }
    require('./update_question.php');	 
    break;
        
 case 'delete_radio':
     survey_data_radio_delete($question_id,$choice_id);
     require('./update_question.php')   ;
     break;
    
 default :
     require('./browse_question.php');
     break;
}

?>
