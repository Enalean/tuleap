<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 
//

require_once('common/dao/include/DataAccessObject.class.php');

/**
 *  Data Access Object for PluginSalomeGroup 
 */
class PluginSalomeGroupDao extends DataAccessObject {
    /**
    * Constructs the PluginSalomeUserDao
    * @param $da instance of the DataAccess class
    */
    function PluginSalomeGroupDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }
    
    /**
    * Gets all groups in the db
    * @return DataAccessResult
    */
    function & searchAll() {
        $sql = "SELECT * FROM GROUPE";
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginSalomeGroup by Codendi GroupId 
    * @return DataAccessResult
    */
    function & searchByGroupId($groupId) {
        $sql = sprintf("SELECT g.*  
                        FROM GROUPE g, CONFIG c
                        WHERE c.CLE = 'cx.trk.grp_id' AND
                              c.VALEUR = %s AND
                              c.id_projet = g.PROJET_VOICE_TESTING_id_projet",
            $this->da->quoteSmart($groupId));
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginSalomeGroup by SalomeGroupId 
    * @return DataAccessResult
    */
    function & searchBySalomeGroupId($salome_group_id) {
        $sql = sprintf("SELECT g.*  
                        FROM GROUPE g
                        WHERE g.id_groupe = %s",
            $this->da->quoteSmart($salome_group_id));
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginSalomeGroup by codendi ugroup_id 
    * @return DataAccessResult
    */
    function & searchByUGroupId($group_id, $ugroup_id) {
        $sql = sprintf("SELECT g.*  
                        FROM GROUPE g, CONFIG c
                        WHERE c.CLE = 'cx.trk.grp_id' AND
                              c.VALEUR = %s AND
                              c.id_projet = g.PROJET_VOICE_TESTING_id_projet AND
                              g.nom_groupe = %s",
            $this->da->quoteSmart($group_id),
            $this->da->quoteSmart($ugroup_id));
        return $this->retrieve($sql);
    }
    
    /**
    * Searches PluginSalomeGroup by salome project Id and Salome group Name 
    * @return DataAccessResult
    */
    function & searchByName($slmProjectId, $slmGroupName) {
        $sql = sprintf("SELECT g.*  
                        FROM GROUPE g
                        WHERE g.PROJET_VOICE_TESTING_id_projet = %s AND
                              g.nom_groupe = %s",
            $this->da->quoteSmart($slmProjectId),
            $this->da->quoteSmart($slmGroupName));
        return $this->retrieve($sql);
    }
    
    /**
    * create a group in the table GROUPE (table for salome group of users) 
    * @return true if there is no error
    */
    function createGroup($salome_project_id, $name, $description, $permission) {
        $sql = sprintf("INSERT INTO GROUPE (PROJET_VOICE_TESTING_id_projet, nom_groupe, desc_groupe, permission) VALUES (%s, %s, %s, %s)",
                $this->da->quoteSmart($salome_project_id),
                $this->da->quoteSmart($name),
                $this->da->quoteSmart($description),
                $this->da->quoteSmart($permission));
        $ok = $this->update($sql);
        return $ok;
    }
    
    /**
     * Update the group in the table GROUPE (table for salome group of users)
     *
     * @param int $group_id the codendi group ID
     * @param int $ugroup_id the codendi ugroup ID
     * @param string $description the new description
     * @return true if there is no error
     */
    function updateGroup($group_id, $ugroup_id, $description) {
        $sql = sprintf(
                "UPDATE GROUPE g, CONFIG c
                SET desc_groupe = %s
                WHERE c.CLE = 'cx.trk.grp_id' AND
                      c.VALEUR = %s AND 
                      g.PROJET_VOICE_TESTING_id_projet = c.id_projet AND 
                      g.nom_groupe = %s",
                $this->da->quoteSmart($description),
                $this->da->quoteSmart($group_id),
                $this->da->quoteSmart($ugroup_id));
        $ok = $this->update($sql);
        return $ok;
    }
    
    /**
    * delete a group in the table GROUPE (table for salome group of users)
    * and remove all users in this group
    * @return true if there is no error
    */
    function deleteGroup($salome_project_id, $name) {
        $sql = sprintf("DELETE FROM PERSONNE_GROUPE WHERE GROUPE_id_groupe = 
                          (
                            SELECT id_groupe 
                            FROM GROUPE 
                            WHERE PROJET_VOICE_TESTING_id_projet = %s AND
                                  nom_groupe = %s
                          )",
                $this->da->quoteSmart($salome_project_id),
                $this->da->quoteSmart($name));
        $this->update($sql);
        $sql = sprintf("DELETE FROM GROUPE 
                        WHERE PROJET_VOICE_TESTING_id_projet = %s AND
                              nom_groupe = %s",
                $this->da->quoteSmart($salome_project_id),
                $this->da->quoteSmart($name));
        $ok = $this->update($sql);
        return $ok;
    }
    
   /**
    * delete all groups of the project $salome_project_id in the table GROUPE (table for salome group of users)
    * and remove all users in these group
    * @return true if there is no error
    */
    function deleteAllGroups($salome_project_id) {
        $sql = sprintf("DELETE FROM PERSONNE_GROUPE WHERE GROUPE_id_groupe IN 
                          (
                            SELECT id_groupe 
                            FROM GROUPE 
                            WHERE PROJET_VOICE_TESTING_id_projet = %s
                          )",
                $this->da->quoteSmart($salome_project_id));
        $this->update($sql);
        $sql = sprintf("DELETE FROM GROUPE 
                        WHERE PROJET_VOICE_TESTING_id_projet = %s",
                $this->da->quoteSmart($salome_project_id));
        $ok = $this->update($sql);
        return $ok;
    }
    
    /**
    * Add user $user_id in group $group_id 
    *
    * @param int $salome_user_id the ID of the salome user
    * @param int $salome_group_id the ID of the salome group
    * @return true if there is no error
    */
    function addUserInGroup($salome_user_id, $salome_group_id) {
        $sql = sprintf("INSERT INTO PERSONNE_GROUPE (PERSONNE_id_personne, GROUPE_id_groupe) VALUES (%s, %s)",
                $this->da->quoteSmart($salome_user_id),
                $this->da->quoteSmart($salome_group_id));
        $ok = $this->update($sql);
        return $ok;
    }
    
    /**
    * Remove user $user_id from group $group_id
    *
    * @param int $salome_user_id the ID of the salome user
    * @param int $salome_group_id the ID of the salome group
    * @return true if there is no error
    */
    function removeUserInGroup($salome_user_id, $salome_group_id) {
        $sql = sprintf("DELETE FROM PERSONNE_GROUPE WHERE PERSONNE_id_personne = %s AND GROUPE_id_groupe = %s",
                $this->da->quoteSmart($salome_user_id),
                $this->da->quoteSmart($salome_group_id));
        $ok = $this->update($sql);
        return $ok;
    }
    
    /**
    * Remove all user in ugroup $ugroup_id
    *
    * @param int $salome_group_id the ID of the salome group
    * @return true if there is no error
    */
    function removeAllUserInGroup($salome_group_id) {
        $sql = sprintf("DELETE FROM PERSONNE_GROUPE WHERE GROUPE_id_groupe = %s",
                $this->da->quoteSmart($salome_group_id));
        $ok = $this->update($sql);
        return $ok;
    }
    
    /**
    * Return true if the user is member of the group, false otherwise
    * 
    * @param int $salome_user_id the ID of the salome user
    * @param int $salome_group_id the ID of the salome group
    * @return true the user is member of the group, false otherwise
    */
    function isUserMemberOf($salome_user_id, $salome_group_id) {
        $sql = sprintf("SELECT * FROM PERSONNE_GROUPE WHERE PERSONNE_id_personne = %s AND GROUPE_id_groupe = %s",
                $this->da->quoteSmart($salome_user_id),
                $this->da->quoteSmart($salome_group_id));
        $dar = $this->retrieve($sql);
        return $dar->rowCount() == 1;
    }
    
    /**
     * Get the permissions of the ugroup $ugroup_id of the group $group_id
     *
     * @param int $group_id the codendi group ID
     * @param int $ugroup_id the codendi ugroup ID
     * @return DataAccessResult
     */
    function getPermissions($group_id, $ugroup_id) {
        $sql = sprintf("
                SELECT permission
                FROM GROUPE g, CONFIG c
                WHERE c.CLE = 'cx.trk.grp_id' AND
                      c.VALEUR = %s AND
                      g.PROJET_VOICE_TESTING_id_projet = c.id_projet AND
                      g.nom_groupe = %s",
                $this->da->quoteSmart($group_id),
                $this->da->quoteSmart($ugroup_id));
        return $this->retrieve($sql);
    }
    
    /**
     * Set the permissions $int_permissions to the ugroup $ugroup_id of the group $group_id
     *
     * @param int $group_id the codendi group ID
     * @param int $ugroup_id the codendi ugroup ID
     * @param int $int_permissions the int value corresponding with the permissions we want set to this ugroup
     * @return true if there is no error
     */
    function setPermissions($group_id, $ugroup_id, $int_permissions) {
        $sql = sprintf(
                "UPDATE GROUPE g, CONFIG c
                SET permission = %s
                WHERE c.CLE = 'cx.trk.grp_id' AND
                      c.VALEUR = %s AND 
                      g.PROJET_VOICE_TESTING_id_projet = c.id_projet AND 
                      g.nom_groupe = %s",
                $this->da->quoteSmart($int_permissions),
                $this->da->quoteSmart($group_id),
                $this->da->quoteSmart($ugroup_id));
        $ok = $this->update($sql);
        return $ok;
    }
    

}


?>