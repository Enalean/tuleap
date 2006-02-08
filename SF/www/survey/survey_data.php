<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX/CodeX Team, 20012 All Rights Reserved
// http://codex.xerox.com
//
// $Id$

$Language->loadLanguageMsg('survey/survey');

function survey_data_survey_create($group_id,$survey_title,$survey_questions,
				   $is_active, $is_anonymous)
{
    global $feedback,$Language;

    $survey_questions = survey_utils_cleanup_questions($survey_questions);

    // Check that the question list only lists existing questions
    if (!survey_utils_all_questions_exist($survey_questions)) {
        $feedback .= " ".$Language->getText('survey_s_data','create_unknown_question',$survey_questions)." ";
        return;
    }

    // Check that the same question does not appear several times
    if (!survey_utils_unique_questions($survey_questions)) {
        $feedback .= " ".$Language->getText('survey_s_data','create_duplicate',$survey_questions)." ";
        return;
    }

    $sql='INSERT INTO surveys (group_id,survey_title,survey_questions,is_active,is_anonymous) '.
	"VALUES ('$group_id','$survey_title','$survey_questions','$is_active','$is_anonymous')";
    $result=db_query($sql);
    if ($result) {
	$feedback .= " ".$Language->getText('survey_s_data','create_succ',db_insertid($result))." ";
    } else {
	$feedback .= " ".$Language->getText('survey_s_data','create_fail',db_error());
    }
}

function survey_data_survey_delete($group_id,$survey_id) {

    global $feedback,$Language;

    // Delete first the data associated with the survey if any
    $res = db_query("DELETE FROM survey_responses WHERE group_id=$group_id AND survey_id=$survey_id");
    // Then delete the survey itself
    $res = db_query("DELETE FROM surveys WHERE survey_id=$survey_id");
    if (db_affected_rows($res) <= 0) {
	    $feedback .= $Language->getText('survey_s_data','del_err',array($survey_id,db_error($res)));
    } else {
	    $feedback .= $Language->getText('survey_s_data','del_succ');
    }    
}

function survey_data_survey_update($group_id,$survey_id,$survey_title,$survey_questions,$is_active,$is_anonymous) {
    
    global $feedback,$Language;
    
    $feedback = '';
    $survey_questions = survey_utils_cleanup_questions($survey_questions);

    // Check that the question list only lists existing questions
    if (!survey_utils_all_questions_exist($survey_questions)) {
        $feedback .= " ".$Language->getText('survey_s_data','upd_unknown_question',$survey_questions)." ";
        return;
    }

    // Check that the same question does not appear several times
    if (!survey_utils_unique_questions($survey_questions)) {
        $feedback .= " ".$Language->getText('survey_s_data','upd_duplicate',$survey_questions)." ";
        return;
    }

    $sql="UPDATE surveys SET survey_title='$survey_title', survey_questions='$survey_questions', is_active='$is_active', is_anonymous='$is_anonymous' ".
		"WHERE survey_id='$survey_id' AND group_id='$group_id'";
    $result=db_query($sql);
    if (db_affected_rows($result) < 1) {
	$feedback .= $Language->getText('survey_s_data','upd_fail',db_error());
    } else {
	$feedback .= ' '.$Language->getText('survey_s_data','upd_succ').' ';
    }
}

function survey_data_question_create($group_id,$question,$question_type)
{   
    global $feedback,$Language;

    $sql='INSERT INTO survey_questions (group_id,question,question_type) '.
	"VALUES ('$group_id','$question','$question_type')";
    $result=db_query($sql);
    if ($result) {
	$feedback .= " ".$Language->getText('survey_s_data','q_create_succ',db_insertid($result))." ";
    } else {
	$feedback .= " ".$Language->getText('survey_s_data','q_create_fail',db_error());
    }
}

function survey_data_question_delete($group_id,$question_id) {

    global $feedback,$Language;

    $feedback = '';
    // Delete first the responses associated with to the question  if any
    $res = db_query("DELETE FROM survey_responses WHERE group_id=$group_id AND survey_id=$question_id");
    // Delete the radio choices if it is a radio button question
    $res = db_query("DELETE FROM survey_radio_choices WHERE question_id=$question_id");
    // Then delete the question itself
    $res = db_query("DELETE FROM survey_questions WHERE group_id=$group_id AND question_id=$question_id");
    if (db_affected_rows($res) <= 0) {
	    $feedback .= $Language->getText('survey_s_data','q_del_fail',db_error($res));
    } else {
	    $feedback .= $Language->getText('survey_s_data','q_del_succ',$question_id);
    }    
}

function survey_data_question_update($group_id,$question_id,$question,$question_type) {
    
    global $feedback,$Language;
    
    $feedback = '';
	$sql="UPDATE survey_questions SET question='$question', question_type='$question_type' ".
		"WHERE question_id='$question_id' AND group_id='$group_id'";
	$result=db_query($sql);
	if (db_affected_rows($result) < 1) {
		$feedback .= ' '.$Language->getText('survey_s_data','upd_fail',db_error());
	} else {
		$feedback .= ' '.$Language->getText('survey_s_data','upd_succ').' ';
	}
}

function survey_data_radio_update($question_id, $choice_id, $radio, $rank) {
    
    global $feedback,$Language;
        
    // cast inputs
    $_question_id = (int) $question_id;
    $_choice_id = (int) $choice_id;
    $_rank = (int) $rank;
    $_radio = htmlentities($radio);
    
    $qry1="SELECT * FROM survey_radio_choices WHERE question_id='$_question_id' AND choice_id='$_choice_id'";
    $res1=db_query($qry1);
    $old_text=db_result($res1,0,'radio_choice');
    $old_rank=db_result($res1,0,'choice_rank');
    
    if (($old_text==$_radio) && ($old_rank==$_rank)) {
        $feedback .= " ".$Language->getText('survey_s_data','upd_fail');
    } else {            
        if ($old_text != $_radio) {            
	    if (check_for_duplicata($_question_id,$_radio)) {
	        $update=true;
	    } else {
	        $feedback .= " ".$Language->getText('survey_s_data','r_update_duplicate');
	    }	    	
        } else {
	    $update=true;
	}    	
    }
    
    if ($update) {
        $sql="UPDATE survey_radio_choices SET radio_choice='$_radio',choice_rank='$_rank'".
            " WHERE question_id='$_question_id' AND choice_id='$_choice_id'";
        $result=db_query($sql);
        if (db_affected_rows($result) < 1) {
	    $feedback .= ' '.$Language->getText('survey_s_data','upd_fail',db_error());
        } else {
	    $feedback .= ' '.$Language->getText('survey_s_data','upd_succ').' ';
        }	   
    }   
}


function survey_data_radio_create($question_id, $radio, $rank) {
    
    global $feedback,$Language;
         		
    // cast inputs
    $_question_id = (int) $question_id;    
    $_rank = (int) $rank;
    $_radio = htmlentities($radio);
    
    if (check_for_duplicata($_question_id,$_radio)) {	
	$sql='INSERT INTO survey_radio_choices (question_id,radio_choice,choice_rank) '.
            "VALUES ('$_question_id','$_radio','$_rank')";
        $result=db_query($sql);
        if ($result) {
	    $feedback .= " ".$Language->getText('survey_s_data','r_create_succ',db_insertid($result))." ";
        } else {
	    $feedback .= " ".$Language->getText('survey_s_data','r_create_fail',db_error());
        }
    } else {
        $feedback .= " ".$Language->getText('survey_s_data','r_create_duplicate');		    
    }
}

function survey_data_radio_delete($question_id, $choice_id) {
    
    global $feedback,$Language;

    // cast inputs
    $_question_id = (int) $question_id;
    $_choice_id = (int) $choice_id;
    
    $sql="DELETE FROM survey_radio_choices WHERE question_id='$_question_id' AND choice_id='$_choice_id'";
    $result=db_query($sql);
    if (db_affected_rows($result) <= 0) {
	    $feedback .= $Language->getText('survey_s_data','r_del_fail',db_error($result));
    } else {
	    $feedback .= $Language->getText('survey_s_data','r_del_succ',$_choice_id);
    }    
    
}   

function check_for_duplicata($question_id, $radio) {

    global $feedback,$Language;
        
    //check if the radio button text is already existing. If so, creation or update fails
    $update=false;
    $qry2="SELECT * FROM survey_radio_choices WHERE question_id='$question_id' AND radio_choice='$radio'";
    $res2=db_query($qry2);
    
    if (db_numrows($res2)>0) {
        $duplicata="";
        $i=0;
        while ((strcmp($duplicata,$radio) != 0) && ($i <= db_numrows($res2))) {
            $duplicata=db_result($res2,$i,'radio_choice');
            $i++;
        }
        // check for upper/lower cases : same texts with different cases (uppercase/lowercase) are allowed
        if ($i > db_numrows($res2)) {
            $update=true;
        } 
    } else {
        $update=true;
    }
    
    return $update;

}

?>
