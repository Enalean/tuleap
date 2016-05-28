<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require('../forum/forum_utils.php');

$request = HTTPRequest::instance();
$HTML->header(array("title"=>$Language->getText('my_monitored_forum', 'title')));
print "<H3>".$Language->getText('my_monitored_forum', 'title')."</H3>\n";
if (user_isloggedin()) {
	/*
		User obviously has to be logged in to monitor
		a thread
	*/

    $vForumId = new Valid_UInt('forum_id');
    $vForumId->required();
	if ($request->valid($vForumId)) {
        $forum_id = $request->get('forum_id');

            	    
	    $forum_monitor_error = false;
		if (user_monitor_forum($forum_id, user_getid())) {
		    // If already monitored then stop monitoring
            forum_delete_monitor($forum_id, user_getid());
            print "<p>".$Language->getText('my_monitored_forum', 'stop_monitoring').
	    "<P><A HREF=\"/my/\">[".$Language->getText('global', 'back_home')."]</A>";
        } else {
		    // Not yet monitored so add it
		    $forum_monitor_error = !forum_add_monitor($forum_id, user_getid());
        }

	} else {
		forum_header(array('title'=>$Language->getText('forum_monitor','choose_forum_first')));
		echo '
			<H1>'.$Language->getText('forum_forum','choose_forum_first').'</H1>';
		forum_footer(array());
	} 

} else {
	exit_not_logged_in();
}
