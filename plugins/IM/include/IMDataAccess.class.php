<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
//

require_once('IMPluginInfo.class.php');

/**
 *  Data Access Object for Plugin 
 */
class IMDataAccess extends DataAccess {
    
    public function __construct($controler) {
        // include the database config file
        $plugin = $controler->getPlugin();
        $etc_root = $plugin->getPluginEtcRoot();
        
        include_once($etc_root . '/database_im.inc');
        $credentials = new DataAccessCredentials($im_dbhost, $im_dbuser, $im_dbpasswd, $im_dbname);
        parent::__construct($credentials);
    }
    
    function instance($controler) {
        static $_imdataaccess_instance;
        if (!$_imdataaccess_instance) {
            $_imdataaccess_instance = new IMDataAccess($controler);
        }
        return $_imdataaccess_instance;
    }
}
