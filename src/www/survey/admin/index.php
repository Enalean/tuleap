<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('www/survey/survey_utils.php');

$Language->loadLanguageMsg('survey/survey');

$is_admin_page='y';

survey_header(array('title'=>$Language->getText('survey_admin_index','admin'),
		    'help'=>'AdministeringSurveys.html'));

if (!user_isloggedin() || !user_ismember($group_id,'A')) {
	echo '<H1>'.$Language->getText('survey_admin_add_question','perm_denied').'</H1>';
	survey_footer(array());
	exit;
}

?>

<H2><?php echo $Language->getText('survey_admin_index','admin'); ?></H2>
<h3><A HREF="/survey/admin/add_survey.php?group_id=<?php echo $group_id; ?>"><?php echo $Language->getText('survey_admin_index','add_s'); ?></A></h3>
<p><?php echo $Language->getText('survey_admin_index','add_comment'); ?>

<h3><A HREF="/survey/admin/edit_survey.php?func=browse&group_id=<?php echo $group_id; ?>"><?php echo $Language->getText('survey_admin_index','edit_existing'); ?></A></h3>
<p><?php echo $Language->getText('survey_admin_index','mod_s'); ?>

<h3><A HREF="/survey/admin/add_question.php?group_id=<?php echo $group_id; ?>"><?php echo $Language->getText('survey_admin_index','add_q'); ?></A></h3>
<p><?php echo $Language->getText('survey_admin_index','create_q'); ?>

<h3><A HREF="/survey/admin/edit_question.php?func=browse&group_id=<?php echo $group_id; ?>"><?php echo $Language->getText('survey_admin_index','edit_existing_q'); ?></A></h3>
<p><?php echo $Language->getText('survey_admin_index','mod_q'); ?>

<h3><A HREF="/survey/admin/show_results.php?group_id=<?php echo $group_id; ?>"><?php echo $Language->getText('survey_admin_index','show_res'); ?></A></h3>
<p><?php echo $Language->getText('survey_admin_index','res'); ?>

<h3><?php echo $Language->getText('survey_admin_index','quick'); ?></h3>
<P>
<?php echo $Language->getText('survey_admin_index','quick_instr',array("http://".$GLOBALS['sys_default_domain']."/survey/survey.php?group_id=$group_id&survey_id=XX","/survey/admin/edit_survey.php?group_id=$group_id",$Language->getText('survey_admin_index','edit_existing'))); ?>

<P>
<?php

survey_footer(array());

?>
