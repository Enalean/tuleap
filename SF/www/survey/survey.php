<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('vote_function.php');
require('../survey/survey_utils.php');
survey_header(array('title'=>'Survey'));

if (!$survey_id || !$group_id) {
	echo "<H1>For some reason, the Group ID or Survey ID did not make it to this page</H1>";
} else {

    // select this survey from the database
    $sql="select * from surveys where survey_id='$survey_id'";
    $result=db_query($sql);

    if (!user_isloggedin() && !db_result($result, 0, "is_anonymous")) {
	/*
		Tell them they need to be logged in
	*/
	echo '<h3><FONT COLOR="RED">You Are NOT logged in.</font></H3>
                        <P>Unfortunately, you have to be logged in to participate in this survey.<BR>
                        <P> Please <b><A HREF="/account/login.php?return_to='.
	      urlencode($REQUEST_URI).'">log in </A> </b> first.</FONT></B>';
	survey_footer(array());
	exit;
    } else {
	show_survey($group_id,$survey_id);
    }
}

survey_footer(array());

?>
