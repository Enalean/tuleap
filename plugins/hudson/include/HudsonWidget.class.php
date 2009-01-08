<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 * abstract class hudson_Widget 
 */

require_once('common/widget/Widget.class.php');
require_once('PluginHudsonJobDao.class.php');
require_once('common/widget/WidgetLayoutManager.class.php');

abstract class HudsonWidget extends Widget {
    
    function getCategory() {
        return 'ci';
    }
    
    protected function getAvailableJobs() {
        $jobs = array();
        $wlm = new WidgetLayoutManager();
        if ($this->owner_type == $wlm->OWNER_TYPE_USER) {
            $jobs = $this->getJobsByUser($user = UserManager::instance()->getCurrentUser()->getId());
        } else {
            $jobs = $this->getJobsByGroup($this->group_id);
        }
        return $jobs;
    }
    
    protected function getJobsByGroup($group_id) {
        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        $dar = $job_dao->searchByGroupID($group_id);
        $jobs = array();
        while ($dar->valid()) {
            $row = $dar->current();
            try {
                $job = new Hudsonjob($row['job_url']);
                $jobs[$row['job_id']] = $job;
            } catch (exception $e) {
                // Do not add unvalid jobs
            }
            $dar->next();
        }
        return $jobs;
    }
    
    protected function getJobsByUser($user_id) {
        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        $dar = $job_dao->searchByUserID($user_id);
        $jobs = array();
        while ($dar->valid()) {
            $row = $dar->current();
            try {
                $job = new Hudsonjob($row['job_url']);
                $jobs[$row['job_id']] = $job;
            } catch (exception $e) {
                // Do not add unvalid jobs
            }
            $dar->next();
        }
        return $jobs;
    }
    
}

?>
