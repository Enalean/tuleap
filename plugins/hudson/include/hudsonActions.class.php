<?php

/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Marc Nazarian, 2008. Xerox Codendi Team.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/mvc/Actions.class.php');
require_once('common/include/HTTPRequest.class.php');

require_once('PluginHudsonJobDao.class.php');

/**
 * hudsonActions
 */
class hudsonActions extends Actions {
    
    function hudsonActions(&$controler, $view=null) {
        $this->Actions($controler);
	}
	
	// {{{ Actions
    function addJob() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_url = $request->get('hudson_job_url');
        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        if ( ! $job_dao->createHudsonJob($group_id, $job_url)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','add_job_error'));
        } else {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_hudson','job_added'));
        }
    }
    function updateJob() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id = $request->get('job_id');
        $new_job_url = $request->get('new_hudson_job_url');
        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        if ( ! $job_dao->updateHudsonJob($job_id, $new_job_url)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','update_job_error'));
        } else {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_hudson','job_updated'));
        }
    }
    function deleteJob() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $job_id = $request->get('job_id');
        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        if ( ! $job_dao->deleteHudsonJob($job_id)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','delete_job_error'));
        } else {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_hudson','job_deleted'));
        }
    }
    // }}}
    
    
}


?>