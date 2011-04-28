<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SalomeTMFProject
 */

require_once('common/plugin/PluginManager.class.php');
require_once('PluginSalomeProjectDao.class.php');

class SalomeTMFProject {

    /**
     * @var int $id the ID of this salome project
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
     * Construct a project from her ID or from her row
     */
    function SalomeTMFProject($salome_project_id, $row = false) {
        if (! $row) {
            $spm =& SalomeTMFProjectManager::instance();
            $p = $spm->getSalomeProjectFromSalomeProjectID($salome_project_id);
            $this->id = $p->getID();
            $this->name = $p->getName();
            $this->description = $p->getDescription();
        } else {
            $this->id = $row['id_projet'];
            $this->name = $row['nom_projet'];
            $this->description = $row['description_projet'];
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

}

?>
