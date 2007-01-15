<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../news/news_utils.php');

$Language->loadLanguageMsg('news/news');

if (user_isloggedin()) {

    if (user_ismember($group_id,'A')) {
 	if (isset($post_changes)) {
            /*
             Insert the row into the db if it's a generic message
             OR this person is an admin for the group involved
            */
            /*
             create a new discussion forum without a default msg
             if one isn't already there
            */

	    news_submit($group_id,$summary,$details,$private_news);
	
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
		<INPUT TYPE="TEXT" NAME="summary" VALUE="" CLASS="textfield_medium">
		<P>
		<B>'.$Language->getText('news_admin_index','details').':</B><BR>
		<TEXTAREA NAME="details" ROWS="8" COLS="50" WRAP="SOFT"></TEXTAREA>
		<P><TABLE BORDER=0>
		<TR><TD><B>'.$Language->getText('news_submit','news_privacy').':</B></TD>
		<TD><INPUT TYPE="RADIO" NAME="private_news" VALUE="0" CHECKED>'. $Language->getText('news_submit','public_news').'</TD></TR> 
		<TR><TD></TD><TD><INPUT TYPE="RADIO" NAME="private_news" VALUE="1">'. $Language->getText('news_submit','private_news').'</TD></TR> 
		</TABLE><P>
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
