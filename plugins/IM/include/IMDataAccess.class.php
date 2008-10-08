<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
//

require_once('common/dao/include/DataAccess.class.php');
require_once('IMPluginInfo.class.php');

/**
 *  Data Access Object for Plugin 
 */
class IMDataAccess extends DataAccess {
    
    function IMDataAccess($controler) {
        // include the database config file
        $plugin = $controler->getPlugin();
        $etc_root = $plugin->getPluginEtcRoot();
        
        include_once($etc_root . '/database_im.inc');
        
        echo 'HOST='.$im_dbhost.'<br>';
        echo 'DBN='.$im_dbname.'<br>';
        echo 'USER='.$im_dbuser.'<br>';
        echo 'PASS='.$im_dbpasswd.'<br>';
                
        $this->DataAccess($im_dbhost, $im_dbuser, $im_dbpasswd, $im_dbname);
    }
    
    function &instance($controler) {
        static $_imdataaccess_instance;
        if (!$_imdataaccess_instance) {
            $_imdataaccess_instance = new IMDataAccess($controler);
        }
        return $_imdataaccess_instance;
    }
}


?>