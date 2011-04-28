<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SalomeTMFUserManager
 */

require_once('common/plugin/PluginManager.class.php');
require_once('salome.class.php');
require_once('PluginSalomeProjectdataDao.class.php');
require_once('SalomeTMFTracker.class.php');
        
class SalomeTMFTrackerManager {

    // the controler of the salome plugin
    var $_controler;
    
    function SalomeTMFTrackerManager() {
        // set the salome plugin controler
        $plugin_manager =& PluginManager::instance();
        $salome_plugin =& $plugin_manager->getPluginByName('salome');
        $this->_controler =& new salome($salome_plugin);
    }
    
    function &instance() {
        static $_salometrackermanager_instance;
        if (!$_salometrackermanager_instance) {
            $_salometrackermanager_instance = new SalomeTMFTrackerManager();
        }
        return $_salometrackermanager_instance;
    }
    
    /**
     * Get the Salome tracker associated with the codendi project $group_id
     *
     * @param string $codendi_group_id the codendi project ID
     * @return {SalomeTMFTracker} the salome tracker, or false if the tracker is not found
     */
    function getSalomeTracker($codendi_group_id) {
        $salome_dao =& new PluginSalomeProjectdataDao(SalomeDataAccess::instance($this->_controler));
        $salome_dar = $salome_dao->searchByGroupId($codendi_group_id);
        if ($salome_dar && $salome_dar->valid()) {
            $row = $salome_dar->current();
            return new SalomeTMFTracker($row);
        } else {
            return false;
        }
    }
    
    /**
     * Create a Salome Tracker with default parameters (from template)
     *
     * @param int $codendi_group_id the Codendi group ID
     * @param int $codendi_group_artifact_id the Codendi tracker ID
     * @param int $codendi_report_id the Codendi report ID
     */
    function createSalomeTrackerFromTemplate($codendi_group_id, $codendi_group_artifact_id, $codendi_report_id) {
        $salome_dao =& new PluginSalomeProjectdataDao(SalomeDataAccess::instance($this->_controler));
        $salome_dao->create($codendi_group_id, 
                            $codendi_group_artifact_id,
                            $codendi_report_id,
                            'slm_environment',
                            'slm_campaign',
                            'slm_family',
                            'slm_suite',
                            'slm_test',
                            'slm_action',
                            'slm_execution',
                            'slm_dataset');
    }

}
?>
