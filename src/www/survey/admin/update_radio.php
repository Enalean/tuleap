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

$Language->loadLanguageMsg('survey/survey');

survey_header(array('title'=>$Language->getText('survey_admin_update_radio','update_r'),
		    'help'=>'AdministeringSurveys.html#CreatingorEditingQuestions'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo '<H1>'.$Language->getText('survey_admin_add_question','perm_denied').'</H1>';
	survey_footer(array());
	exit;
}

// fetch question and associated radio button from DB, and check for integrity IDs
$sql1="SELECT * FROM survey_questions WHERE question_id='$question_id'";
$res1=db_query($sql1);
if (db_numrows($res1) == 0) {
    $feedback .= " Error finding question #".$question_id;
} else {
    $q_type=db_result($res1,0,'question_type');
    $sql2="SELECT * FROM survey_radio_choices WHERE question_id='$question_id' AND choice_id='$choice_id'"; 
    $res2=db_query($sql2);
    if (db_numrows($res2) == 0) {
        $feedback .= " Error finding radio button #".$choice_id;
    }
}    

?>

<P>
<H2>
<?php
if ($q_type == 6) {
    //radio buttons
    echo $Language->getText('survey_admin_update_radio','update_r'); 
} else {
    //select-box
    echo $Language->getText('survey_admin_update_radio','update_sb'); 
}
?>
</H2>
<P>

<?php

if ((db_numrows($res1) != 0) && (db_numrows($res2) != 0)) {
    survey_utils_show_radio_form($question_id, $choice_id, $q_type);
}    
survey_footer(array());

?>




