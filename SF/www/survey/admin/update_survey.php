<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2002. All rights reserved
//
// $Id$

$Language->loadLanguageMsg('survey/survey');

$is_admin_page='y';
survey_header(array('title'=>$Language->getText('survey_admin_browse_question','edit_a_s')));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo '<H1>'.$Language->getText('survey_admin_add_question','perm_denied').'</H1>';
	survey_footer(array());
	exit;
}

/*
	Get this survey out of the DB
*/
if ($survey_id) {
	$sql="SELECT * FROM surveys WHERE survey_id='$survey_id' AND group_id='$group_id'";
	$result=db_query($sql);
	$survey_title=db_result($result, 0, "survey_title");
	$survey_questions=db_result($result, 0, "survey_questions");
	$is_active=db_result($result, 0, "is_active");
	$is_anonymous=db_result($result, 0, "is_anonymous");
}
?>
<SCRIPT LANGUAGE="JavaScript">
<!--
var timerID2 = null;

function show_questions() {
        newWindow = open("","occursDialog","height=600,width=500,scrollbars=yes,resizable=yes");
        newWindow.location=('show_questions.php?group_id=<?php echo $group_id; ?>');
}

// -->
</script>

<?php
if ($survey_id) {
?>
<H2><?php echo $Language->getText('survey_admin_browse_question','edit_a_s'); ?></H2><P>

<H3><span class="highlight"><?php echo $Language->getText('survey_admin_update_survey','warn'); ?>
<P>
<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="func" VALUE="update_survey">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
<B><?php echo $Language->getText('survey_admin_add_survey','s_name'); ?></B>
<BR>
<INPUT TYPE="HIDDEN" NAME="survey_id" VALUE="<?php echo $survey_id; ?>">
<INPUT TYPE="TEXT" NAME="survey_title" VALUE="<?php echo $survey_title; ?>" SIZE="30" MAXLENGTH="150">
<P>
<B><?php echo $Language->getText('survey_admin_update_survey','q'); ?></B>
<BR>
<?php echo $Language->getText('survey_admin_add_survey','comment'); ?>
<BR><INPUT TYPE="TEXT" NAME="survey_questions" VALUE="<?php echo $survey_questions; ?>" SIZE="30" MAXLENGTH="1500">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<INPUT TYPE="BUTTON" NAME="none" VALUE="<?php echo $Language->getText('survey_admin_add_question','show_q'); ?>" ONCLICK="show_questions()">
<table border="0">
<tr><td><B><?php echo $Language->getText('survey_admin_add_survey','active'); ?></B></td>
<td><INPUT TYPE="RADIO" NAME="is_active" VALUE="1"<?php if ($is_active=='1') { echo ' CHECKED'; } ?>> <?php echo $Language->getText('global','yes'); ?></td>
<td><INPUT TYPE="RADIO" NAME="is_active" VALUE="0"<?php if ($is_active=='0') { echo ' CHECKED'; } ?>> <?php echo $Language->getText('global','no'); ?></td>
<tr>
<tr><td><B><?php echo $Language->getText('survey_admin_update_survey','anon_allow'); ?></B></td>
<td><INPUT TYPE="RADIO" NAME="is_anonymous" VALUE="1"<?php if ($is_anonymous=='1') { echo ' CHECKED'; } ?>> <?php echo $Language->getText('global','yes'); ?></td>
<td><INPUT TYPE="RADIO" NAME="is_anonymous" VALUE="0"<?php if ($is_anonymous=='0') { echo ' CHECKED'; } ?>> <?php echo $Language->getText('global','no'); ?></td>
</tr>
</table>
<P>
<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="<?php echo $Language->getText('survey_admin_update_question','subm_changes'); ?>">
</FORM>  

<?php
/*
	Select all surveys from the database
*/

$sql="SELECT * FROM surveys WHERE group_id='$group_id'";

$result=db_query($sql);

?>

<P>
<H3><?php echo $Language->getText('survey_admin_add_survey','existing_s'); ?></H3>
<?php

survey_utils_show_surveys($result);

survey_footer(array());

}
?>
