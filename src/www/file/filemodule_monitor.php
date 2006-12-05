<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
$Language->loadLanguageMsg('file/file');

if (user_isloggedin()) {
	/*
		User obviously has to be logged in to monitor
		a file module
	*/

	$HTML->header(array('title'=>$Language->getText('file_filemodule_monitor','monitor_a_package')));

	if ($filemodule_id) {
		/*
			First check to see if they are already monitoring
			this thread. If they are, say so and quit.
			If they are NOT, then insert a row into the db
		*/

		echo '
			<H2>'.$Language->getText('file_filemodule_monitor','monitor_package').'</H2>';

		$sql="SELECT * FROM filemodule_monitor WHERE user_id='".user_getid()."' AND filemodule_id='$filemodule_id';";

		$result = db_query($sql);

		if (!$result || db_numrows($result) < 1) {
			/*
				User is not already monitoring this filemodule, so 
				insert a row so monitoring can begin
			*/
			$sql="INSERT INTO filemodule_monitor (filemodule_id,user_id) VALUES ('$filemodule_id','".user_getid()."')";

			$result = db_query($sql);

			if (!$result) {
				echo '
					<span class="highlight">'.$Language->getText('file_filemodule_monitor','insert_err').'</span>';
			} else {
				echo '
					<span class="highlight"><H3>'.$Language->getText('file_filemodule_monitor','p_monitored').'</H3></span>
					<P>
					'.$Language->getText('file_filemodule_monitor','now_emails').'
					<P>
					'.$Language->getText('file_filemodule_monitor','turn_monitor_off');
			}

		} else {

			$sql="DELETE FROM filemodule_monitor WHERE user_id='".user_getid()."' AND filemodule_id='$filemodule_id';";
			$result = db_query($sql);
			echo '
				<span class="highlight"><H3>'.$Language->getText('file_filemodule_monitor','monitor_turned_off').'</H3></span>
				<P>
				'.$Language->getText('file_filemodule_monitor','no_emails');

		}

	} else {
		echo '
			<H1>'.$Language->getText('file_filemodule_monitor','choose_p').'</H1>';
	} 

	$HTML->footer(array());

} else {
	exit_not_logged_in();
}
?>
