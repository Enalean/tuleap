<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 
//

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for PluginSalomeUser 
 */
class PluginSalomeUserDao extends DataAccessObject {
    /**
    * Constructs the PluginSalomeUserDao
    * @param $da instance of the DataAccess class
    */
    function PluginSalomeUserDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all users in the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM PERSONNE";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginSalomeUser by GroupId 
    * @return DataAccessResult
    */
    function & searchByGroupId($groupId) {
        $sql = sprintf("SELECT p.*  
                        FROM PERSONNE p, CONFIG c
                        WHERE c1.CLE = 'cx.trk.grp_id' AND
                              c1.VALEUR = %s AND
                              c1.id_projet = p.id_projet'",
            $this->da->quoteSmart($groupId));
        return $this->retrieve($sql);
    }

    /**
    * Searches PluginSalomeUser by Login 
    * @return DataAccessResult
    */
    function & searchByLogin($loginName) {
        $sql = sprintf("SELECT p.*  
                        FROM PERSONNE p
                        WHERE p.login_personne = %s",
            $this->da->quoteSmart($loginName));
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginSalomeUser by salome ID 
    * @return DataAccessResult
    */
    function & searchBySalomeID($salome_user_id) {
        $sql = sprintf("SELECT p.*  
                        FROM PERSONNE p
                        WHERE p.id_personne = %s",
            $this->da->quoteSmart($salome_user_id));
        return $this->retrieve($sql);
    }
    
    /**
    * create a row in the table PERSONNE (table for salome users) 
    * @return true if there is no error
    */
    function create($login_name) {
        $sql = sprintf("INSERT INTO PERSONNE (login_personne, nom_personne, prenom_personne, date_creation_personne, heure_creation_personne) VALUES (%s, %s, %s, %s, %s)",
                $this->da->quoteSmart($login_name),
                $this->da->quoteSmart($login_name),
                $this->da->quoteSmart(''),
                $this->da->quoteSmart(date("Y-m-d")),
                $this->da->quoteSmart(date("H:i:s")));
        $ok = $this->update($sql);
        return $ok;
    }
    
    

}


?>