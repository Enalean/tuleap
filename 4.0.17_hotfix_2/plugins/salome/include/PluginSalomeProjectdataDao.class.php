<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 
//

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for PluginSalomeProjectdata 
 */
class PluginSalomeProjectdataDao extends DataAccessObject {
    /**
    * Constructs the PluginSalomeProjectdataDao
    * @param $da instance of the DataAccess class
    */
    function PluginSalomeProjectdataDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM CONFIG";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginSalomeProjectdata by GroupId 
    * @return DataAccessResult
    */
    function & searchByGroupId($groupId) {
        $sql = sprintf("SELECT c2.VALEUR as group_artifact_id, 
                               c11.VALEUR as report_id,
                               c3.VALEUR as environment_field, 
                               c4.VALEUR as campaign_field, 
                               c5.VALEUR as family_field,
                               c6.VALEUR as suite_field,
                               c7.VALEUR as test_field,
                               c8.VALEUR as action_field,
                               c9.VALEUR as execution_field,
                               c10.VALEUR as dataset_field
                        FROM CONFIG c1, CONFIG c2, CONFIG c3, CONFIG c4, CONFIG c5, CONFIG c6, CONFIG c7, CONFIG c8, CONFIG c9, CONFIG c10, CONFIG c11
                        WHERE c1.CLE = 'cx.trk.grp_id' AND
                              c1.VALEUR = %s AND
                              c2.id_projet = c1.id_projet AND 
                              c3.id_projet = c1.id_projet AND
                              c4.id_projet = c1.id_projet AND
                              c5.id_projet = c1.id_projet AND
                              c6.id_projet = c1.id_projet AND
                              c7.id_projet = c1.id_projet AND
                              c8.id_projet = c1.id_projet AND
                              c9.id_projet = c1.id_projet AND
                              c10.id_projet = c1.id_projet AND
                              c11.id_projet = c1.id_projet AND
                              c2.CLE = 'cx.trk.grp_art_id' AND
                              c3.CLE = 'cx.trk.env.fld_nm' AND
                              c4.CLE = 'cx.trk.camp.fld_nm' AND
                              c5.CLE = 'cx.trk.family.fld_nm' AND
                              c6.CLE = 'cx.trk.suite.fld_nm' AND
                              c7.CLE = 'cx.trk.test.fld_nm' AND
                              c8.CLE = 'cx.trk.action.fld_nm' AND
                              c9.CLE = 'cx.trk.exec.fld_nm' AND
                              c10.CLE = 'cx.trk.dtset.fld_nm' AND
                              c11.CLE = 'cx.trk.env.report_id'",
            $this->da->quoteSmart($groupId));
        return $this->retrieve($sql);
    }

    /**
    * create a row in the table plugin_salome_projectdata 
    * @return true or id(auto_increment) if there is no error
    */
    function create($group_id, $group_artifact_id, $report_id, $environment_field, $campaign_field, $family_field, $suite_field, $test_field, $action_field, $execution_field, $dataset_field) {
        // retrieve the "id_projet"
        $id_projet = null;
        $sql = sprintf("SELECT id_projet FROM CONFIG WHERE CLE = 'cx.trk.grp_id' AND VALEUR = %s",
            $this->da->quoteSmart($group_id));
        $dar = $this->retrieve($sql);
        if ($dar && $dar->valid()) {
            $row = $dar->current();
            $id_projet = $row['id_projet'];
        }
        if (isset($id_projet) && $id_projet) {
            // link 'salome' tracker with salome DB
            $sql = sprintf("INSERT INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.grp_art_id', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($group_artifact_id));
            $inserted = $this->update($sql);
            // link 'salome' report with salome DB
            $sql = sprintf("INSERT INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.env.report_id', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($report_id));
            $inserted = $this->update($sql);
            // link 'salome' tracker field 'environment' with salome DB
            $sql = sprintf("INSERT INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.env.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($environment_field));
            $inserted = $this->update($sql);
            // link 'salome' tracker field 'campaign' with salome DB
            $sql = sprintf("INSERT INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.camp.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($campaign_field));
            $inserted = $this->update($sql);
            // link 'salome' tracker field 'family' with salome DB
            $sql = sprintf("INSERT INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.family.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($family_field));
            $inserted = $this->update($sql);
            // link 'salome' tracker field 'suite' with salome DB
            $sql = sprintf("INSERT INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.suite.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($suite_field));
            $inserted = $this->update($sql);
            // link 'salome' tracker field 'test' with salome DB
            $sql = sprintf("INSERT INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.test.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($test_field));
            $inserted = $this->update($sql);
            // link 'salome' tracker field 'action' with salome DB
            $sql = sprintf("INSERT INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.action.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($action_field));
            $inserted = $this->update($sql);
            // link 'salome' tracker field 'execution' with salome DB
            $sql = sprintf("INSERT INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.exec.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($execution_field));
            $inserted = $this->update($sql);
            // link 'salome' tracker field 'dataset' with salome DB
            $sql = sprintf("INSERT INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.dtset.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($dataset_field));
            $inserted = $this->update($sql);
            return $inserted;
        } else {
            return false;
        }
        
    }
    
    function updateByGroupId($group_id, $group_artifact_id, $report_id, $environment_field, $campaign_field, $family_field, $suite_field, $test_field, $action_field, $execution_field, $dataset_field) {
        // retrieve the "id_projet"
        $id_projet = null;
        $sql = sprintf("SELECT id_projet FROM CONFIG WHERE CLE = 'cx.trk.grp_id' AND VALEUR = %s",
            $this->da->quoteSmart($group_id));
        $dar = $this->retrieve($sql);
        if ($dar && $dar->valid()) {
            $row = $dar->current();
            $id_projet = $row['id_projet'];
        }
        if (isset($id_projet) && $id_projet) {
            $sql = sprintf("REPLACE INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.grp_art_id', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($group_artifact_id));
            $updated = $this->update($sql);
            $sql = sprintf("REPLACE INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.env.report_id', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($report_id));
            $updated = $this->update($sql);
            $sql = sprintf("REPLACE INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.env.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($environment_field));
            $updated = $this->update($sql);
            $sql = sprintf("REPLACE INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.camp.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($campaign_field));
            $updated = $this->update($sql);
            $sql = sprintf("REPLACE INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.family.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($family_field));
            $updated = $this->update($sql);
            $sql = sprintf("REPLACE INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.suite.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($suite_field));
            $updated = $this->update($sql);
            $sql = sprintf("REPLACE INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.test.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($test_field));
            $updated = $this->update($sql);
            $sql = sprintf("REPLACE INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.action.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($action_field));
            $updated = $this->update($sql);
            $sql = sprintf("REPLACE INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.exec.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($execution_field));
            $updated = $this->update($sql);
            $sql = sprintf("REPLACE INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.dtset.fld_nm', %s, 0, %s)",
                $this->da->quoteSmart($id_projet),
                $this->da->quoteSmart($dataset_field));
            $updated = $this->update($sql);
     
            return $updated;
        } else {
            return false;
        }
    }
    

}


?>