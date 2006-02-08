<?php

/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('pre.php');
require('../survey_data.php');
require('../survey_utils.php');

$Language->loadLanguageMsg('survey/survey');

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	survey_header(array('title'=>$Language->getText('survey_admin_update_radio','update_r'),
		    'help'=>'AdministeringSurveys.html#CreatingorEditingQuestions'));
	echo '<H1>'.$Language->getText('survey_admin_add_question','perm_denied').'</H1>';
	survey_footer(array());
	exit;
}

if (array_key_exists('confirm', $_POST) && isset($_POST['confirm'])) {
    
    // Update the question	 
    survey_data_question_update($group_id, $question_id, htmlspecialchars($question), $question_type);
    
    $sql = "SELECT * FROM survey_radio_choices WHERE question_id='$question_id'";
    $result = db_query($sql);
    $rows = db_numrows($result);
    if ($rows > 0) {
        for ($j=0; $j<$rows; $j++) {
        $radio_id=db_result($result,$j,'choice_id');
	    survey_data_radio_delete($question_id,$radio_id);
        }
    }
    
    session_redirect("/survey/admin/edit_question.php?func=browse&group_id=$group_id");
}

if (array_key_exists('cancel', $_POST) && isset($_POST['cancel'])) {
    session_redirect("/survey/admin/edit_question.php?func=update_question&group_id=$group_id&question_id=$question_id"); 
}

survey_header(array('title'=>$Language->getText('survey_admin_update_radio','update_r'),
		    'help'=>'AdministeringSurveys.html#CreatingorEditingQuestions'));

// fetch question and associated radio button from DB, and check for integrity IDs
$qry="SELECT * FROM survey_questions WHERE question_id='$question_id'";
$res=db_query($qry);
if (db_numrows($res) == 0) {
    $feedback .= " Error finding question #".$question_id;
    survey_footer(array());
    exit;
} else {
    echo '<h2><font color=red>'.$Language->getText('survey_s_utils','warn_lose_button').'</font></h2>';    
}
?>

<P>
<TABLE><FORM ACTION="?" METHOD="POST">
<TD><INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id ; ?>"></TD>
<TD><INPUT TYPE="HIDDEN" NAME="question_id" VALUE="<?php echo $question_id ; ?>"></TD>
<TD><INPUT TYPE="HIDDEN" NAME="question" VALUE="<?php echo $question ; ?>"></TD>
<TD><INPUT TYPE="HIDDEN" NAME="question_type" VALUE="<?php echo $question_type ; ?>"></TD>
<TD COLSPAN="5"></TD>
<TR><TD><INPUT TYPE="SUBMIT" NAME="confirm" VALUE="Continue"></TD>
<TD COLSPAN="5"></TD>
<TD><INPUT TYPE="SUBMIT" NAME="cancel" VALUE="Cancel"></TD></TR>
</FORM></TABLE>
</P>

<?php

survey_footer(array());

?>
