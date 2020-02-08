<?php
/*
 * Copyright (c) Enalean, 2011 - 2015. All Rights Reserved.
 * Copyright (c) Xerox, 2009. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2009. Xerox Codendi Team.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Base class to read forge configuration
 */
class ForgeConfig
{

    public const AUTH_TYPE_LDAP = 'ldap';

    /**
     * Hold the configuration variables
     */
    protected static $conf_stack = array(0 => array());

    /**
     * Load the configuration variables into the current stack
     *
     * @access protected for testing purpose
     *
     */
    protected static function load(ConfigValueProvider $value_provider)
    {
        // Store in the stack the local scope...
        self::$conf_stack[0] = array_merge(self::$conf_stack[0], $value_provider->getVariables());
    }

    public static function loadFromFile($file)
    {
        self::load(new ConfigValueFileProvider($file));
    }

    public static function loadFromDatabase()
    {
        self::load(new ConfigValueDatabaseProvider(new ConfigDao()));
    }

    /**
     * Get the $name configuration variable
     *
     * @param $name    string the variable name
     * @param $default mixed  the value to return if the variable is not set in the configuration. TODO: read in the local.inc.dist
     *
     * @return mixed
     */
    public static function get($name, $default = false)
    {
        if (self::exists($name)) {
            return self::$conf_stack[0][$name];
        }
        return $default;
    }

    public static function exists($name)
    {
        return isset(self::$conf_stack[0][$name]);
    }

    public static function getSuperPublicProjectsFromRestrictedFile()
    {
        $filename = $GLOBALS['Language']->getContent('include/restricted_user_permissions', 'en_US');
        if (! $filename) {
            return array();
        }

        $public_projects = array();
        include($filename);

        return $public_projects;
    }

    /**
     * Dump the content of the config for debugging purpose
     *
     * @return void
     */
    public static function dump()
    {
        var_export(self::$conf_stack[0]);
    }

    /**
     * Store and clear the current stack. Only useful for testing purpose. DON'T USE IT IN PRODUCTION
     * @see ConfigTest::setUp() for details
     *
     * @return void
     */
    public static function store()
    {
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
    public static function restore()
    {
        if (count(self::$conf_stack) > 1) {
            array_shift(self::$conf_stack);
        }
    }

    /**
     * Set a configuration value. Only useful for testing purpose. DON'T USE IT IN PRODUCTION
     *
     * @param $name String Variable name
     * @param $value Mixed Variable value
     */
    public static function set($name, $value)
    {
        self::$conf_stack[0][$name] = $value;
    }

    public static function areAnonymousAllowed()
    {
        return self::get(ForgeAccess::CONFIG) === ForgeAccess::ANONYMOUS ||
                PermissionsOverrider_PermissionsOverriderManager::instance()->doesOverriderForceUsageOfAnonymous();
    }

    public static function areRestrictedUsersAllowed()
    {
        return self::get(ForgeAccess::CONFIG) === ForgeAccess::RESTRICTED;
    }

    public static function getApplicationUserLogin()
    {
        return self::get('sys_http_user');
    }

    public static function areUnixGroupsAvailableOnSystem()
    {
        return trim(self::get('grpdir_prefix')) !== '';
    }

    public static function areUnixUsersAvailableOnSystem()
    {
        return trim(self::get('homedir_prefix')) !== '';
    }

    public static function getCacheDir()
    {
        return self::get('codendi_cache_dir');
    }
}
