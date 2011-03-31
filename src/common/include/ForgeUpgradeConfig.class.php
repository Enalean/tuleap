<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 * Manage interactions with ForgeUpgrade configuration
 */
class ForgeUpgradeConfig {
    protected $filePath;
    protected $config;

    /**
     * Constructor
     *
     * @param String $filePath Path to a .ini config file
     */
    public function __construct($filePath=null) {
        if (is_file($filePath)) {
            $this->setFilePath($filePath);
        }
    }

    /**
     * Initialize object with given config file
     *
     * @param String $filePath Path to an .ini file
     */
    public function setFilePath($filePath) {
        $this->filePath = $filePath;
        $this->config   = parse_ini_file($this->filePath, true);
    }

    /**
     * Test is given path exists in core.path section of configuration
     *
     * @param String $path A path to test
     *
     * @return Boolean
     */
    public function existsInPath($path) {
        if (isset($this->config['core']['path'])) {
            return in_array($path, $this->config['core']['path']);
        }
        return false;
    }
}

?>
