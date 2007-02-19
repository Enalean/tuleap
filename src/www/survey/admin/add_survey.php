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

require_once('common/include/HTTPRequest.class.php');
$request =& HTTPRequest::instance();

$Language->loadLanguageMsg('survey/survey');

$is_admin_page='y';

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
    survey_header(array('title'=>$Language->getText('survey_admin_add_survey','add_s'),
		    'help'=>'AdministeringSurveys.html#CreatingorEditingaSurvey'));
	echo '<H1>'.$Language->getText('survey_admin_add_question','perm_denied').'</H1>';
	survey_footer(array());
	exit;
}

if ($request->exist('post_changes')) {
    survey_data_survey_create($group_id,$survey_title,$survey_questions,
			      $is_active, $is_anonymous);
}

survey_header(array('title'=>$Language->getText('survey_admin_add_survey','add_s'),
		    'help'=>'AdministeringSurveys.html#CreatingorEditingaSurvey'));

?>
<SCRIPT LANGUAGE="JavaScript">
<!--
var timerID2 = null;

function show_questions() {
        newWindow = open("","occursDialog","height=600,width=700,scrollbars=yes,resizable=yes");
        newWindow.location=('show_questions.php?group_id=<?php echo $group_id; ?>');
}

// -->
</script>

<H2><?php echo $Language->getText('survey_admin_add_survey','add_s'); ?></H2><P>

<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">

<B><?php echo $Language->getText('survey_admin_add_survey','s_name'); ?></B>
<BR>
<INPUT TYPE="TEXT" NAME="survey_title" VALUE="" SIZE="60" MAXLENGTH="150"><P>
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
<?php echo $Language->getText('survey_admin_add_survey','comment'); ?>
<BR><INPUT TYPE="TEXT" NAME="survey_questions" VALUE="" SIZE="60" MAXLENGTH="1500">
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<INPUT TYPE="BUTTON" NAME="none" VALUE="<?php echo $Language->getText('survey_admin_add_question','show_q'); ?>" ONCLICK="show_questions()">
<p>
<table border="0">
<tr><td><B><?php echo $Language->getText('survey_admin_add_survey','active'); ?></B></td>
<td><INPUT TYPE="RADIO" NAME="is_active" VALUE="1" CHECKED> <?php echo $Language->getText('global','yes'); ?></td>
<td><INPUT TYPE="RADIO" NAME="is_active" VALUE="0"> <?php echo $Language->getText('global','no'); ?></td>
<tr>
<tr><td><B>Anonymous answer ok?</B></td>
<td><INPUT TYPE="RADIO" NAME="is_anonymous" VALUE="1"> <?php echo $Language->getText('global','yes'); ?></td>
<td><INPUT TYPE="RADIO" NAME="is_anonymous" VALUE="0" CHECKED> <?php echo $Language->getText('global','no'); ?></td>
</tr>
</table>
<P>
<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="<?php echo $Language->getText('survey_admin_add_survey','add_this_s'); ?>">
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
?>
