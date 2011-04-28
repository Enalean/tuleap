<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SalomeTMFTracker
 */

require_once('common/plugin/PluginManager.class.php');
require_once('PluginSalomeProjectdataDao.class.php');

class SalomeTMFTracker {

    /**
     * @var int $codendi_tracker_id the ID of the codendi tracker
     */
    var $codendi_tracker_id;
    
    /**
     * @var int $codendi_report_id the ID of the codendi report
     */
    var $codendi_report_id;
    
    /**
     * @var array $special_fields the salome_special fields
     */
    var $special_fields;
    
    /**
     * 
     */
    function SalomeTMFTracker($row) {
        $this->codendi_tracker_id = $row['group_artifact_id'];
        $this->codendi_report_id = $row['report_id'];
        
        $this->special_fields['environment_field'] = $row['environment_field'];;
        $this->special_fields['campaign_field'] = $row['campaign_field'];
        $this->special_fields['family_field'] = $row['family_field'];
        $this->special_fields['suite_field'] = $row['suite_field'];
        $this->special_fields['test_field'] = $row['test_field'];
        $this->special_fields['action_field'] = $row['action_field'];
        $this->special_fields['execution_field'] = $row['execution_field'];
        $this->special_fields['dataset_field'] = $row['dataset_field'];
    }
    
    function getCodendiTrackerID() {
        return $this->codendi_tracker_id;
    }
    
    function getCodendiReportID() {
        return $this->codendi_report_id;
    }
    
    function getSpecialFields() {
        return $this->special_fields;
    }
    
    function getSpecialField($field) {
        return $this->special_fields[$field];
    }
    
    function isWellConfigured() {
        $tracker_id_ok = ($this->codendi_tracker_id != null);
        $report_id_ok = ($this->codendi_report_id != null);
        $special_fields_ok = true;
        foreach ($this->special_fields as $key => $value) {
            $special_fields_ok = $special_fields_ok && ($this->special_fields[$key] != '0');
        }
        return $tracker_id_ok && $report_id_ok && $special_fields_ok;
    }
    
}

?>
