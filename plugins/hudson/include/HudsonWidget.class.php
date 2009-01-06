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

abstract class HudsonWidget extends Widget {
    
    function getCategory() {
        return 'ci';
    }
    
    protected function _getMonitoredJobsByGroup() {
        $job_dao = new PluginHudsonJobDao(CodexDataAccess::instance());
        $dar = $job_dao->searchByGroupID($this->group_id);
        $monitored_jobs = array();
        while ($dar->valid()) {
            $row = $dar->current();
            $monitored_jobs[] = $row['job_id'];                    
            $dar->next();
        }
        return $monitored_jobs;
    }
    
}

?>
