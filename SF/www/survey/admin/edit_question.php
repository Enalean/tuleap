<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
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
	 // Update the question
	 survey_data_question_update($group_id, $question_id, 
		 htmlspecialchars($question), $question_type);
	 // Display the list after the update
	 require('./browse_question.php');
     } else {
	 // Show the form to update the question
	 require('./update_question.php');
     }
    break;
    
 default :
     require('./browse_question.php');
     break;
}

?>
