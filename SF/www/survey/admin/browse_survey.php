<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2002. All rights reserved
//
// $Id$


survey_header(array('title'=>'Edit Surveys'));


/*
	Select all surveys from the database
*/

$sql="SELECT * FROM surveys WHERE group_id='$group_id'";

$result=db_query($sql);

?>

<P>
<H2>Existing Surveys</H2>
<?php

ShowResultsEditSurvey($result);

survey_footer(array());
?>
