<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id:PluginDescriptor.class.php 4446 2006-12-08 16:18:48 +0000 (Fri, 08 Dec 2006) ahardyau $
 *
 * PluginDescriptor
 */
class PluginDescriptor {
    
    var $name;
    var $version;
    var $description;
    
    function PluginDescriptor($name = '', $version = '', $description = '') {
        $this->name        = $name;
        $this->version     = $version;
        $this->description = $description;
        $this->icon_name   = '';
        
    }
    
    function getFullName()        { return $this->name; }
    function getVersion()     { return $this->version; }
    function getDescription() { return $this->description; }
}
?>