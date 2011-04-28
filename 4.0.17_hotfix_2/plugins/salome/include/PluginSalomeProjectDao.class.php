<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 
//

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for PluginSalomeProject 
 */
class PluginSalomeProjectDao extends DataAccessObject {
    /**
    * Constructs the PluginSalomeProjectDao
    * @param $da instance of the DataAccess class
    */
    function PluginSalomeProjectDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all projects in the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM PROJET_VOICE_TESTING";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginSalomeProject by GroupId 
    * @return DataAccessResult
    */
    function & searchByGroupId($groupId) {
        $sql = sprintf("SELECT p.*  
                        FROM PROJET_VOICE_TESTING p, CONFIG c
                        WHERE c.CLE = 'cx.trk.grp_id' AND
                              c.VALEUR = %s AND
                              c.id_projet = p.id_projet",
            $this->da->quoteSmart($groupId));
        return $this->retrieve($sql);
    }

    /**
    * Searches PluginSalomeProject by SalomeProjectId 
    * @return DataAccessResult
    */
    function & searchBySalomeProjectId($salome_project_id) {
        $sql = sprintf("SELECT p.*  
                        FROM PROJET_VOICE_TESTING p
                        WHERE p.id_projet = %s",
            $this->da->quoteSmart($salome_project_id));
        return $this->retrieve($sql);
    }
    
    /**
    * create a row in the table PROJECT_VOICE_TESTING (table for salome projects) 
    * @return true if there is no error
    */
    function create($group_id, $group_name, $group_description) {
        $sql = sprintf("INSERT INTO PROJET_VOICE_TESTING (nom_projet, description_projet, date_creation_projet, verrou_projet) VALUES (%s, %s, %s, NULL)",
                $this->da->quoteSmart($group_name),
                $this->da->quoteSmart($group_description),
                $this->da->quoteSmart(date("Y-m-d")));
        $ok = $this->update($sql);
        if ($ok) {
            $salome_project_id = $this->da->lastInsertId();
            $sql = sprintf("INSERT INTO CONFIG (CLE, id_projet, id_personne, VALEUR) VALUES ('cx.trk.grp_id', %s, 0, %s)",
                    $this->da->quoteSmart($salome_project_id),
                    $this->da->quoteSmart($group_id));
            $ok_config = $this->update($sql);
            if ($ok_config) {
                return $salome_project_id;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    function updateByGroupId($group_id, $group_name, $group_description) {
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
            $sql = sprintf("UPDATE PROJET_VOICE_TESTING SET nom_projet = %s, description_projet = %s WHERE id_projet = %s",
                $this->da->quoteSmart($group_name),
                $this->da->quoteSmart($group_description),
                $this->da->quoteSmart($id_projet));
            $updated = $this->update($sql);
            return $updated;
        } else {
            return false;
        }
    }
    

}


?>