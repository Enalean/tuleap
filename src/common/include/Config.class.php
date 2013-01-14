<?php
/*
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
 

/**
 * Base class to read forge configuration
 */
class Config {

    const AUTH_TYPE_LDAP = 'ldap';

    
    /**
     * Hold the configuration variables
     */
    protected static $conf_stack = array(0 => array());
    
    /**
     * Load the configuration variables into the current stack
     *
     * @param $file string /path/to/config.file.php
     *
     * @return boolean true if success false otherwise
     */
    public static function load($file) {
        if (is_file($file) && is_readable($file)) {
            // include the file in the local scope
            include($file);
            
            // Store in the stack the local scope...
            self::$conf_stack[0] = array_merge(self::$conf_stack[0], get_defined_vars());
            
            // ...but filter out the local parameter '$file'
            if (self::$conf_stack[0]['file'] === $file) {
                unset(self::$conf_stack[0]['file']);
            }
            return true;
        }
        return false;
    }
    
    /**
     * Get the $name configuration variable
     *
     * @param $name    string the variable name
     * @param $default mixed  the value to return if the variable is not set in the configuration. TODO: read in the local.inc.dist
     *
     * @return mixed
     */
    public static function get($name, $default = false) {
        if (isset(self::$conf_stack[0][$name])) {
            return self::$conf_stack[0][$name];
        }
        return $default;
    }
    
    /**
     * Dump the content of the config for debugging purpose
     *
     * @return void
     */
    public static function dump() {
        var_export(self::$conf_stack[0]);
    }
    
    /**
     * Store and clear the current stack. Only useful for testing purpose. DON'T USE IT IN PRODUCTION
     * @see ConfigTest::setUp() for details
     * 
     * @return void
     */
    public static function store() {
        array_unshift(self::$conf_stack, array());
        if (!count(self::$conf_stack)) {
            trigger_error('Config registry lost');
        }
    }
    
    /**
     * Restore the previous stack. Only useful for testing purpose. DON'T USE IT IN PRODUCTION
     * @see ConfigTest::tearDown() for details
     * 
     * @return void
     */
    public static function restore() {
        array_shift(self::$conf_stack);
    }
    
    /**
     * Set a configuration value. Only useful for testing purpose. DON'T USE IT IN PRODUCTION
     *
     * @param $name String Variable name
     * @param $value Mixed Variable value
     */
    public static function set($name, $value) {
        self::$conf_stack[0][$name] = $value;
    }
}
?>
