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
 	if (isset($post_changes)) {
            /*
             Insert the row into the db if it's a generic message
             OR this person is an admin for the group involved
            */
            /*
             create a new discussion forum without a default msg
             if one isn't already there
            */

            //if news is declared as private, force the $promote_news to '0' value (to not be promoted)
	    if ($promote_news == '3' && $private_news == '3') {
		$promote_news="0";
	    }	    
	    
	    $new_id=forum_create_forum($GLOBALS['sys_news_group'],$summary,1,0);
            $sql="INSERT INTO news_bytes (group_id,submitted_by,is_approved,date,forum_id,summary,details) ".
                " VALUES ('$group_id','".user_getid()."','$promote_news','".time()."','$new_id','".htmlspecialchars($summary)."','".htmlspecialchars($details)."')";
            $result=db_query($sql);
               
	    if (!$result) {
                $feedback .= ' '.$Language->getText('news_submit','insert_err').' ';
            } else {
                $feedback .= ' '.$Language->getText('news_submit','news_added').' ';
		// set permissions on this piece of news
		$ugroup_id=$private_news;  
		news_insert_permissions($new_id,$ugroup_id);
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
		<INPUT TYPE="TEXT" NAME="summary" VALUE="" SIZE="44" MAXLENGTH="60">
		<P>
		<B>'.$Language->getText('news_admin_index','details').':</B><BR>
		<TEXTAREA NAME="details" ROWS="8" COLS="50" WRAP="SOFT"></TEXTAREA>
		<P><TABLE BORDER=0>
		<TR><TD><B>'.$Language->getText('news_submit','news_privacy').':</B></TD>
		<TD><INPUT TYPE="RADIO" NAME="private_news" VALUE="1" CHECKED>'. $Language->getText('news_submit','public_news').'</TD></TR> 
		<TR><TD></TD><TD><INPUT TYPE="RADIO" NAME="private_news" VALUE="3">'. $Language->getText('news_submit','private_news').'</TD></TR> 
		</TABLE><P>
		<TABLE BORDER=0>
		<TR><TD><B>'.$Language->getText('news_submit','news_promote',$GLOBALS['sys_name']).' : </B></TD>
		<TD><INPUT TYPE="RADIO" NAME="promote_news" VALUE="3" CHECKED>'.$Language->getText('global','yes').'</TD>
		<TD><INPUT TYPE="RADIO" NAME="promote_news" VALUE="0">'.$Language->getText('global','no').'</TD>
		</TR></TABLE><P>		
		<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_submit').'">
		</FORM>';

	$feedback = $Language->getText('news_submit','promote_warn',$GLOBALS['sys_name']);
	news_footer(array());

    } else {
        exit_error($Language->getText('news_admin_index','permission_denied'),$Language->getText('news_submit','only_admin_submits'));
    }
} else {
    exit_not_logged_in();
}
?>
