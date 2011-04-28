<?php
/**
 * Copyright (c) Xerox Corporation, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'PluginInfo.class.php';
require_once 'common/include/PropertyDescriptor.class.php';

/**
 * File based plugin options management
 */
class PluginFileInfo extends PluginInfo
{
    protected $conf_path;
    
    /**
     * Constructor
     *
     * @param Plugin $plugin  The plugin on which PluginInfo applies
     * @param String $incname Name of the '.inc' file in plugin 'etc' directory
     */
    function __construct(Plugin $plugin, $incname) 
    {
        parent::__construct($plugin);
        $this->conf_path = $plugin->getPluginEtcRoot() .'/'.$incname.'.inc';
        $this->loadProperties();
    }
    
    /**
     * Load properties from the configuration file
     */
    function loadProperties() 
    {
        if (file_exists($this->conf_path)) {
            $this->checkConfigurationFiles($this->conf_path);
            $variables = $this->getVariablesFromConfigurationFile($this->conf_path);
            foreach ($variables as $variable) {
                $key = $variable['name'];
                if (preg_match('`^"(.*)"$`', $variable['value'], $match) ||
                preg_match('`^\'(.*)\'$`', $variable['value'], $match)) {
                    $value = $match[1];
                } else {
                    $value = $variable['value'];
                }
                $descriptor = new PropertyDescriptor($key, $value);
                $this->_addPropertyDescriptor($descriptor);
            }
        }
    }

    /**
     * Save in memory properties in the configuration file
     */
    public function saveProperties() 
    {
        copy($this->conf_path, $this->conf_path .'.'. date('YmdHis'));
        $content = file_get_contents($this->conf_path);
        $descs   =& $this->getPropertyDescriptors();
        $keys    =& $descs->getKeys();
        $iter    =& $keys->iterator();
        while ($iter->valid()) {
            $key       =& $iter->current();
            $desc      =& $descs->get($key);
            $desc_name =& $desc->getName();
            if (is_bool($desc->getValue())) {
                $replace = '$1'. ($desc->getValue() ? 'true' : 'false') .';';
            } else {
                $replace = '$1"'.addslashes($desc->getValue()).'";';
            }
            $content = preg_replace('`((?:^|\n)\$'. preg_quote($desc_name) .'\s*=\s*)(.*)\s*;`', 
                $replace, 
                $content);
            $iter->next();
        }
        $f = fopen($this->conf_path, 'w');
        if ($f) {
            fwrite($f, $content);
            fclose($f);
        }
    }

    /**
     * Return the property value for given property name
     *
     * @param String $name Label of the property
     * 
     * @return String
     */
    function getPropertyValueForName($name) 
    {
        $desc = $this->getPropertyDescriptorForName($name);
        return $desc ? $desc->getValue() : $desc;
    }

    /**
     * Alias for getPropertyValueForName
     *
     */
    function getPropVal($name)
    {
        return $this->getPropertyValueForName($name);
    }
    
    /**
     * Extract PHP variables from the config file
     *
     * @param String $file Full path to the configuration file
     * 
     * @return Array All the variables defined in the file
     */
    protected function getVariablesFromConfigurationFile($file) 
    {
        $tokens = token_get_all(file_get_contents($file));

        $variables = array();
        $current   = 0;
        foreach ($tokens as $token) {
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

    /**
     * Check if the configuration file is valid or not 
     *
     */
    protected function checkConfigurationFiles() 
    {
        require $this->conf_path;
    }
}
?>