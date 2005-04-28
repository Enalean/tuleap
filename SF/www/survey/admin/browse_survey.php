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

survey_header(array('title'=>$Language->getText('survey_admin_browse_question','edit_s_title'),
		    'help'=>'AdministeringSurveys.html#CreatingorEditingaSurvey'));


/*
	Select all surveys from the database
*/

$sql="SELECT * FROM surveys WHERE group_id='$group_id'";

$result=db_query($sql);

?>

<P>
<H2><?php echo $Language->getText('survey_admin_browse_question','edit_s_hdr'); ?></H2>
<?php echo $Language->getText('survey_admin_browse_survey','edit_s_msg'); ?>
<?php

survey_utils_show_surveys($result);

survey_footer(array());
?>
