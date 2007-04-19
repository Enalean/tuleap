<?php
require_once('common/collection/Map.class.php');
require_once('PluginDescriptor.class.php');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * PluginInfo
 */
class PluginInfo {
    
    var $plugin;
    var $pluginDescriptor;
    var $propertyDescriptors;
    
    function PluginInfo(&$plugin) {
        $this->plugin              =& $plugin;
        $this->propertyDescriptors =& new Map();
    }
    
    function setPluginDescriptor(&$descriptor) {
        $this->pluginDescriptor =& $descriptor;
    }
    
    function &getPluginDescriptor() {
        if (!is_a($this->pluginDescriptor, 'PluginDescriptor')) {
            $this->setPluginDescriptor(new PluginDescriptor('', '', ''));
        }
        return $this->pluginDescriptor;
    }
    function &getPropertyDescriptors() {
        return $this->propertyDescriptors;
    }
    
    function _addPropertyDescriptor(&$descriptor) {
        $name =& $descriptor->getName();
        $this->propertyDescriptors->put($name, $descriptor);
    }
    function _removePropertyDescriptor(&$descriptor) {
        $name =& $descriptor->getName();
        return $this->propertyDescriptors->remove($name, $descriptor);
    }
    
    function loadProperties() {
    }
    
    function saveProperties() {
    }
    
    function getPropertyDescriptorForName($name) {
        $n = new String($name);
        return $this->propertyDescriptors->get($n);
    }
}
?>