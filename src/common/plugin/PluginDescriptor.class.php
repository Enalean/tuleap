<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
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