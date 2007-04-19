<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: filemodule_monitor.php 5275 2007-03-13 10:55:33 +0000 (Tue, 13 Mar 2007) mnazaria $

require_once('pre.php');
require_once('common/frs/FileModuleMonitorFactory.class.php');
$Language->loadLanguageMsg('file/file');

if (user_isloggedin()) {
	/*
		User obviously has to be logged in to monitor
		a file module
	*/
	if (isset($filemodule_id)) {
		/*
			First check to see if they are already monitoring
			this thread. If they are, say so and quit.
			If they are NOT, then insert a row into the db
		*/
		$frsfmf = new FileModuleMonitorFactory();

		if (!$frsfmf->isMonitoring($filemodule_id)) {
			/*
				User is not already monitoring this filemodule, so 
				insert a row so monitoring can begin
			*/
            
			$result = $frsfmf->setMonitor($filemodule_id);

			if (!$result) {
				$GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor','insert_err'));
			} else {
			    $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor','p_monitored'));
                $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor','now_emails'));
                $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor','turn_monitor_off'));
			}

		} else {
			$result = $frsfmf->stopMonitor($filemodule_id);
            $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor','monitor_turned_off'));
            $GLOBALS['Response']->addFeedback('info', $Language->getText('file_filemodule_monitor','no_emails'));
		}

	} else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_filemodule_monitor','choose_p'));
	}
    
    // redirect the user to the page she went
    if (array_key_exists('HTTP_REFERER', $_SERVER)) {
        $redirection_url = $_SERVER['HTTP_REFERER'];
    } else {
        $redirection_url = '../my/';
    }
    $GLOBALS['Response']->redirect($redirection_url);

} else {
	exit_not_logged_in();
}
?>
