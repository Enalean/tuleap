<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * PluginCreationWizardPluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');

$GLOBALS['Language']->loadLanguageMsg('plugincreationwizard', 'plugincreationwizard');

class PluginCreationWizardPluginDescriptor extends PluginDescriptor {
    
    function PluginCreationWizardPluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_plugincreationwizard', 'descriptor_name'), 'v1.0', $GLOBALS['Language']->getText('plugin_plugincreationwizard', 'descriptor_description'));
    }
}
?>