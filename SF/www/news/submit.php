<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../forum/forum_utils.php');

$Language->loadLanguageMsg('news/news');

if (user_isloggedin()) {

    if (user_ismember($group_id,'A')) {
 	if ($post_changes) {
            /*
             Insert the row into the db if it's a generic message
             OR this person is an admin for the group involved
            */
            /*
             create a new discussion forum without a default msg
             if one isn't already there
            */

            $new_id=forum_create_forum($GLOBALS['sys_news_group'],$summary,1,0);
            $sql="INSERT INTO news_bytes (group_id,submitted_by,is_approved,date,forum_id,summary,details) ".
                " VALUES ('$group_id','".user_getid()."','0','".time()."','$new_id','".htmlspecialchars($summary)."','".htmlspecialchars($details)."')";
            $result=db_query($sql);
            if (!$result) {
                $feedback .= ' '.$Language->getText('news_submit','insert_err').' ';
            } else {
                $feedback .= ' '.$Language->getText('news_submit','news_added').' ';
            }
	}

	//news must now be submitted from a project page - 

	if (!$group_id) {
            exit_no_group();
	}
	/*
         Show the submit form
	*/
	news_header(array('title'=>$Language->getText('news_index','news'),
			  'help'=>'NewsService.html'));

        /*
         create a new discussion forum without a default msg
         if one isn't already there
        */
        echo '
		<H3>'.$Language->getText('news_submit','submit_news_for',group_getname($group_id)).'</H3>
		<P>
		'.$Language->getText('news_submit','post_explain',$GLOBALS['sys_name']).'
		<P>
		<FORM ACTION="'.$PHP_SELF.'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<B>'.$Language->getText('news_submit','for_project',group_getname($group_id)) .'</B>
		<INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="y">
		<P>
		<B>'.$Language->getText('news_admin_index','subject').':</B><BR>
		<INPUT TYPE="TEXT" NAME="summary" VALUE="" SIZE="30" MAXLENGTH="60">
		<P>
		<B>'.$Language->getText('news_admin_index','details').':</B><BR>
		<TEXTAREA NAME="details" ROWS="5" COLS="50" WRAP="SOFT"></TEXTAREA><BR>
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
		</FORM>';

	news_footer(array());

    } else {
        exit_error($Language->getText('news_admin_index','permission_denied'),$Language->getText('news_submit','only_admin_submits'));
    }
} else {
    exit_not_logged_in();
}
?>
