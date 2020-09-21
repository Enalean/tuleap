<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han <xiphux@gmail.com>
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

namespace Tuleap\Git\GitPHP;

/**
 * GitPHP Config
 *
 * Configfile reader class
 *
 */
/**
 * Config class
 */
class Config
{

    /**
     * instance
     *
     * Stores the singleton instance
     *
     * @access protected
     * @static
     * @var self|null
     */
    protected static $instance;

    /**
     * values
     *
     * Stores the config values
     *
     * @access protected
     */
    protected $values = [];

    /**
     * configs
     *
     * Stores the config files
     *
     * @access protected
     */
    protected $configs = [];

    /**
     * GetInstance
     *
     * Returns the singleton instance
     *
     * @access public
     * @static
     * @return self instance of config class
     */
    public static function GetInstance() // @codingStandardsIgnoreLine
    {
        if (! self::$instance) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    /**
     * LoadConfig
     *
     * Loads a config file
     *
     * @access public
     * @param string $configFile config file to load
     * @throws \Exception on failure
     */
    public function LoadConfig($configFile) // @codingStandardsIgnoreLine
    {
        if (! is_file($configFile)) {
            throw new MessageException('Could not load config file ' . $configFile, true, 500);
        }

        if (! include($configFile)) {
            throw new MessageException('Could not read config file ' . $configFile, true, 500);
        }

        if (isset($gitphp_conf) && is_array($gitphp_conf)) {
            $this->values = array_merge($this->values, $gitphp_conf);
        }

        $this->configs[] = $configFile;
    }

    /**
     * ClearConfig
     *
     * Clears all config values
     *
     * @access public
     */
    public function ClearConfig() // @codingStandardsIgnoreLine
    {
        $this->values = [];
        $this->configs = [];
    }

    /**
     * GetValue
     *
     * Gets a config value
     *
     * @access public
     * @return mixed config value
     */
    public function GetValue($key, $default = null) // @codingStandardsIgnoreLine
    {
        if ($this->HasKey($key)) {
            return $this->values[$key];
        }
        return $default;
    }

    /**
     * SetValue
     *
     * Sets a config value
     *
     * @access public
     * @param string $key config key to set
     * @param mixed $value value to set
     */
    public function SetValue($key, $value) // @codingStandardsIgnoreLine
    {
        if (empty($key)) {
            return;
        }
        if (empty($value)) {
            unset($this->values[$key]);
            return;
        }
        $this->values[$key] = $value;
    }

    /**
     * HasKey
     *
     * Tests if the config has specified this key
     *
     * @access public
     * @param string $key config key to find
     * @return bool true if key exists
     */
    public function HasKey($key) // @codingStandardsIgnoreLine
    {
        if (empty($key)) {
            return false;
        }
        return isset($this->values[$key]);
    }
}
