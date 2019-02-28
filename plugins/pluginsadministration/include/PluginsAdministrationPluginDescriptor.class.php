<?php

require_once('common/plugin/PluginDescriptor.class.php');


/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * PluginsAdministrationPluginDescriptor
 */
class PluginsAdministrationPluginDescriptor extends PluginDescriptor {
    
    function __construct() {
        $name        = dgettext('tuleap-pluginsadministration', 'Plugins Administration');
        $description = dgettext('tuleap-pluginsadministration', 'Offers a web interface for managing plugins.');
        parent::__construct($name, false, $description);
        $this->setVersionFromFile(dirname(__FILE__).'/../VERSION');
    }
    
}
?>