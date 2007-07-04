<?php


/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 *
 * CvsToDimensionsPluginInfo
 */
require_once('common/plugin/PluginInfo.class.php');
require_once('common/include/PropertyDescriptor.class.php');
require_once('CvsToDimensionsPluginDescriptor.class.php');

class CvsToDimensionsPluginInfo extends PluginInfo {
    
    function CvsToDimensionsPluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new CvsToDimensionsPluginDescriptor());
        $this->_conf_path = $plugin->getPluginEtcRoot() .'/cvstodmensions.inc';
        $this->loadProperties();
    }
    
    function loadProperties() {
        if (file_exists($this->_conf_path)) {
            $this->checkConfigurationFiles($this->_conf_path);
            $variables = $this->_getVariablesFromConfigurationFile($this->_conf_path);
            foreach($variables as $variable) {
                $key =& new String($variable['name']);
                if (preg_match('`^"(.*)"$`', $variable['value'], $match) || 
                    preg_match('`^\'(.*)\'$`', $variable['value'], $match)) 
                {
                    $value = $match[1];
                } 
                else 
                {
                    $value = $variable['value'];
                }
                $descriptor =& new PropertyDescriptor($key, $value);
                $this->_addPropertyDescriptor($descriptor);
            }
        }
    }
    
    function saveProperties() {
        copy($this->_conf_path, $this->_conf_path .'.'. date('YmdHis'));
        $content = file_get_contents($this->_conf_path);
        $descs =& $this->getPropertyDescriptors();
        $keys  =& $descs->getKeys();
        $iter  =& $keys->iterator();
        while($iter->valid()) {
            $key   =& $iter->current();
            $desc  =& $descs->get($key);
            $desc_name =& $desc->getName();
            if (is_bool($desc->getValue())) {
                $replace = '$1'. ($desc->getValue() ? 'true' : 'false') .';';
            } else {
                $replace = '$1"'.addslashes($desc->getValue()).'";';
            }
            $content = preg_replace(
                '`((?:^|\n)\$'. preg_quote($desc_name->getInternalString()) .'\s*=\s*)(.*)\s*;`', 
                $replace, 
                $content
            );
            $iter->next();
        }
        $f = fopen($this->_conf_path, 'w');
        if ($f) {
            fwrite($f, $content);
            fclose($f);
        }
    }
    
    function getPropertyValueForName($name) {
        $desc = $this->getPropertyDescriptorForName($name);
        return $desc ? $desc->getValue() : $desc;
    }
    
    function _getVariablesFromConfigurationFile($file) {
        $tokens = token_get_all(file_get_contents($file));

        $variables = array();
        $current = 0;
        foreach($tokens as $token) {
            switch ($token[0]) {
                case T_VARIABLE:
                    $variables[$current] = array('name' => substr($token[1], 1), 'value' => '');
                    break;
                case T_STRING:
                case T_CONSTANT_ENCAPSED_STRING:
                case T_DNUMBER:
                case T_LNUMBER:
                case T_NUM_STRING:
                    if (T_STRING == $token[0] && (!strcasecmp($token[1], "false") || !strcasecmp($token[1], "true"))) {
                        $val = (bool)strcasecmp($token[1], "false");
                        if (isset($variables[$current])) {
                            $variables[$current]['value'] = $val;
                        }
                    } else {
                        if (isset($variables[$current])) {
                            $variables[$current]['value'] .= $token[1];
                        }
                    }
                    break;
                case '*':
                    if (isset($variables[$current])) {
                        $variables[$current]['value'] .= $token[0];
                    }
                    break;
                case ';':
                    $current++;
                    break;
                default:
                    break;
            }
        }
        return $variables;
    }
    
    function checkConfigurationFiles() {
        require($this->_conf_path);
    }
}
?>