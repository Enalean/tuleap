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

?>
