<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../forum/forum_utils.php');
$Language->loadLanguageMsg('forum/forum');

if (user_isloggedin()) {
	/*
		User obviously has to be logged in to monitor
		a thread
	*/

	if ($forum_id) {

            // Check permissions
            if (!forum_utils_access_allowed($forum_id)) {
                exit_error($Language->getText('global','error'),$Language->getText('forum_forum','forum_restricted'));            
            }
	    
	    //If the forum is associated to a private news, non-allowed users shouldn't be able to monitor this forum
	    $qry = "SELECT * FROM news_bytes WHERE forum_id='$forum_id'";
	    $res = db_query($qry);
	    if (db_numrows($res) > 0) {
	        if (!forum_utils_news_access($forum_id)) {	    
	            exit_error($Language->getText('global','error'),$Language->getText('news_admin_index','permission_denied'));
	        }
	    }

		/*
			First check to see if they are already monitoring
			this thread. If they are, say so and quit.
			If they are NOT, then insert a row into the db
		*/

		/*
			Set up navigation vars
		*/
		$result=db_query("SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id='$forum_id'");

		$group_id=db_result($result,0,'group_id');
		$forum_name=db_result($result,0,'forum_name');

		forum_header(array('title'=>$Language->getText('forum_monitor','monitor')));

		echo '
			<H2>'.$Language->getText('forum_monitor','monitor').'</H2>';

		if (forum_is_monitored($forum_id, user_getid())) {
		  if ($pv) {
		    echo "<span class=\"highlight\"><H3>".$Language->getText('forum_monitor','now_monitoring')."</H3></span>";
		    echo '<P>'.$Language->getText('forum_monitor','get_followups');
		    echo '<P>'.$Language->getText('forum_monitor','to_turn_monitor_off');
		  } else {
		    // If already monitored then stop monitoring
		    forum_delete_monitor ($forum_id, user_getid());
		    echo "<span class=\"highlight\"><H3>".$Language->getText('forum_monitor','monitor_off')."</H3></span>";
		    echo '<P>'.$Language->getText('forum_monitor','no_mails_anymore');
		  }
		} else {
		  if ($pv) {
		    echo "<span class=\"highlight\"><H3>".$Language->getText('forum_monitor','monitor_off')."</H3></span>";
		    echo '<P>'.$Language->getText('forum_monitor','no_mails_anymore');
		  } else {
		    // Not yet monitored so add it
		    if (forum_add_monitor ($forum_id, user_getid()) ) {
			echo "<span class=\"highlight\"><H3>".$Language->getText('forum_monitor','now_monitoring')."</H3></span>";
			echo '<P>'.$Language->getText('forum_monitor','get_followups');
			echo '<P>'.$Language->getText('forum_monitor','to_turn_monitor_off');	
		    } else {
			echo "<span class=\"highlight\">".$Language->getText('forum_forum_utils','insert_err')."</span>";
		    }
		  }
		}
		forum_footer(array());

	} else {
		forum_header(array('title'=>$Language->getText('forum_monitor','choose_forum_first')));
		echo '
			<H1>'.$Language->getText('forum_forum','choose_forum_first').'</H1>';
		forum_footer(array());
	} 

} else {
	exit_not_logged_in();
}
?>
