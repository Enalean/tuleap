<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SalomeTMFGroup
 */

require_once('common/plugin/PluginManager.class.php');
require_once('PluginSalomeGroupDao.class.php');

class SalomeTMFGroup {

    /**
     * @var int $id the ID of this salome group
     */
    var $id;
    
    /**
     * @var string $name the name of this salome group
     */
    var $name;
    
    /**
     * @var string $description the description of this salome group
     */
    var $description;
    
    /**
     * @var int $permissions the permissions for this salome group
     */
    var $permissions;
    
    /**
     * @var int $salome_project_id the Id of the salome project this group belongs to
     */
    var $salome_project_id;
    
    /**
     * Construct a group from her ID or from her row
     */
    function SalomeTMFGroup($salome_group_id, $row = false) {
        if (! $row) {
            $sgm =& SalomeTMFGroupManager::instance();
            $g = $sgm->getSalomeGroupFromSalomeGroupID($salome_group_id);
            $this->id = $g->getID();
            $this->name = $g->getName();
            $this->description = $g->getDescription();
            $this->permissions = $g->getPermissions();
            $this->salome_project_id = $g->getSalomeProjectID();
        } else {
            $this->id = $row['id_groupe'];
            $this->name = $row['nom_groupe'];
            $this->description = $row['desc_groupe'];
            $this->permissions = $row['permission'];
            $this->salome_project_id = $row['PROJET_VOICE_TESTING_id_projet'];
        }
    }
    
    function getID() {
        return $this->id;
    }
    
    function getName() {
        return $this->name;
    }
    
    function getDescription() {
        return $this->description;
    }
    
    function getPermissions() {
        return $this->permissions;
    }
    
    function getSalomeProjectID() {
        return $this->salome_project_id;
    }
    
}

?>
