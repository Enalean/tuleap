<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require('../forum/forum_utils.php');

if (user_isloggedin()) {

	if ($post_changes) {
		/*
			Insert the row into the db if it's a generic message
			OR this person is an admin for the group involved
		*/
		if (user_ismember($group_id,'A')) {
			/*
				create a new discussion forum without a default msg
				if one isn't already there
			*/

			$new_id=forum_create_forum($GLOBALS['sys_news_group'],$summary,1,0);
			$sql="INSERT INTO news_bytes (group_id,submitted_by,is_approved,date,forum_id,summary,details) ".
				" VALUES ('$group_id','".user_getid()."','0','".time()."','$new_id','".htmlspecialchars($summary)."','".htmlspecialchars($details)."')";
			$result=db_query($sql);
			if (!$result) {
				$feedback .= ' ERROR doing insert ';
			} else {
				$feedback .= ' News Added. ';
			}
		} else {
			exit_error('Permission Denied.','Permission Denied. You cannot submit news for a project unless you are an admin on that project');
		}
	}

	//news must now be submitted from a project page - 

	if (!$group_id) {
		exit_no_group();
	}
	/*
		Show the submit form
	*/
	news_header(array('title'=>'News',
			  'help'=>'NewsService.html'));

	echo '
		<H3>Submit News For '.group_getname($group_id).'</H3>
		<P>
		You can post news about your project if you are an admin on your project. 
		You may also post "help wanted" notes if your project needs help.
		<P>
		All posts <B>for your project</B> will appear instantly on your project 
		summary page. Posts that are of special interest to the community will 
		have to be approved by a member of the news team before they will appear 
		on the '.$GLOBALS['sys_name'].' home page.
		<P>
		You may include URLs, but not HTML in your submissions.
		<P>
		URLs that start with http:// are made clickable.
		<P>
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<B>For Project: '. group_getname($group_id) .'</B>
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<P>
		<B>Subject:</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" VALUE="" SIZE="30" MAXLENGTH="60">
		<P>
		<B>Details:</B><BR>
		<TEXTAREA NAME="details" ROWS="5" COLS="50" WRAP="SOFT"></TEXTAREA><BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="SUBMIT">
		</FORM>';

	news_footer(array());

} else {

	exit_not_logged_in();

}
?>
