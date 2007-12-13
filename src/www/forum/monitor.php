<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require('../forum/forum_utils.php');
$Language->loadLanguageMsg('forum/forum');

$request =& HTTPRequest::instance();

if (user_isloggedin()) {
	/*
		User obviously has to be logged in to monitor
		a thread
	*/

    $vForumId = new Valid_UInt('forum_id');
    $vForumId->required();
	if ($request->valid($vForumId)) {
        $forum_id = $request->get('forum_id');

            // Check permissions
            if (!forum_utils_access_allowed($forum_id)) {
                exit_error($Language->getText('global','error'),$Language->getText('forum_forum','forum_restricted'));            
            }
	    
	    //If the forum is associated to a private news, non-allowed users shouldn't be able to monitor this forum
	    // but they should be able to disable monitoring news that have been set from public to private
	    $qry = "SELECT * FROM news_bytes WHERE forum_id=".db_ei($forum_id);
	    $res = db_query($qry);
	    if (db_numrows($res) > 0) {
	        if (!forum_utils_news_access($forum_id) && !forum_is_monitored($forum_id, user_getid())) {	    
	            exit_error($Language->getText('global','error'),$Language->getText('news_admin_index','permission_denied'));
	        }
	    }
        
        $forum_monitor_error = false;
		if (forum_is_monitored($forum_id, user_getid())) {
		    // If already monitored then stop monitoring
            forum_delete_monitor($forum_id, user_getid());
        } else {
		    // Not yet monitored so add it
		    $forum_monitor_error = !forum_add_monitor($forum_id, user_getid());
        }

		/*
			Set up navigation vars
		*/
		$result=db_query("SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id=".db_ei($forum_id));

		$group_id=db_result($result,0,'group_id');
		$forum_name=db_result($result,0,'forum_name');

		forum_header(array('title'=>$Language->getText('forum_monitor','monitor')));

		echo '
			<H2>'.$Language->getText('forum_monitor','monitor').'</H2>';

		if (forum_is_monitored($forum_id, user_getid())) {
            echo "<span class=\"highlight\"><H3>".$Language->getText('forum_monitor','now_monitoring')."</H3></span>";
            echo '<P>'.$Language->getText('forum_monitor','get_followups').'</p>';
            echo '<P>'.$Language->getText('forum_monitor','to_turn_monitor_off').'</p>';
		} else {
		    echo "<span class=\"highlight\"><H3>".$Language->getText('forum_monitor','monitor_off')."</H3></span>";
		    echo '<P>'.$Language->getText('forum_monitor','no_mails_anymore').'</p>';
		}
        if ($forum_monitor_error) {
            echo "<span class=\"highlight\">".$Language->getText('forum_forum_utils','insert_err')."</span>";
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
