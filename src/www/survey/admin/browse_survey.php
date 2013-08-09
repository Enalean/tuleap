<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 


survey_header(array('title'=>$Language->getText('survey_admin_browse_survey','edit_s'),
		    'help'=>'survey.html#creating-or-editing-a-survey'));


/*
	Select all surveys from the database
*/

$sql="SELECT * FROM surveys WHERE group_id='$group_id'";

$result=db_query($sql);

?>

<P>
<H2><?php echo $Language->getText('survey_admin_browse_survey','edit_s'); ?></H2>
<?php echo $Language->getText('survey_admin_browse_survey','edit_s_msg'); ?>
<?php

survey_utils_show_surveys($result);

survey_footer(array());
?>
