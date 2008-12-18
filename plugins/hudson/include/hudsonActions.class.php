<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * HudsonActions
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