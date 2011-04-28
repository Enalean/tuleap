<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SalomeTMFPluginSalome
 */

class SalomeTMFPluginSalome {

    /**
     * @var string $name the name of the plugin
     */
    var $name;
    
    /**
     * @var string $path the path to the plugin installation directory
     */
    var $path;
    
    function SalomeTMFPluginSalome($name, $path) {
        $this->name = $name;
        $this->path = $path;
    }
    
    function getName() {
        return $this->name;
    }
    
    function getPath() {
        return $this->path;
    }
    
}

?>
