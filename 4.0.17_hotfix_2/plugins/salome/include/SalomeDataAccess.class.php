<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 
//

require_once('common/dao/include/DataAccess.class.php');
require_once('salomePluginInfo.class.php');

/**
 *  Data Access Object for Plugin 
 */
class SalomeDataAccess extends DataAccess {
    
    function SalomeDataAccess($controler) {
        // include the database config file
        $plugin = $controler->getPlugin();
        $etc_root = $plugin->getPluginEtcRoot();
        
        include($etc_root . '/' . $controler->getProperty('salome_db_config_file'));
        
        try {
        	$this->DataAccess($salome_dbhost, $salome_dbuser, $salome_dbpasswd, $salome_dbname);
        } catch (DataAccessException $dae) {
        	throw new DataAccessException('Unable to access '.$salome_dbname.' database. Please contact your administrator.');
        }
        
    }
    
    function &instance($controler) {
        static $_salomedataaccess_instance;
        if (!$_salomedataaccess_instance) {
            $_salomedataaccess_instance = new SalomeDataAccess($controler);
        }
        return $_salomedataaccess_instance;
    }
}


?>