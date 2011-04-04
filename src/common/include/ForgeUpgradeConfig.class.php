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
     * Load default codendi config as defined in configuration
     */
    public function loadDefaults() {
        if (isset($GLOBALS['forgeupgrade_file']) && is_file($GLOBALS['forgeupgrade_file'])) {
            $this->setFilePath($GLOBALS['forgeupgrade_file']);
        } else {
            $localInc = getenv('CODENDI_LOCAL_INC')?getenv('CODENDI_LOCAL_INC'):'/etc/codendi/conf/local.inc';
            throw new Exception('$forgeupgrade_file variable not defined in '.$localInc);
        }
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

    /**
     * Add a new path in the core.path section
     *
     * @param String $path The path to add
     */
    public function addPath($path) {
        if (!isset($this->config['core'])) {
            $this->config['core'] = array();
        }
        if (!isset($this->config['core']['path'])) {
            $this->config['core']['path'] = array();
        }
        $this->config['core']['path'][] = $path;
        $this->write();
    }

    /**
     * Write (override) forgeupgrade config file based on in memory status
     *
     * @see http://stackoverflow.com/questions/1268378/create-ini-file-write-values-in-php
     */
    protected function write() {
        $content = '';

        foreach ($this->config as $key=>$elem) {
            $content .= '['.$key.']'.PHP_EOL;
            foreach ($elem as $key2=>$elem2) {
                if(is_array($elem2)) {
                    for($i=0;$i<count($elem2);$i++) {
                        $content .= $key2.'[] = "'.$elem2[$i].'"'.PHP_EOL;
                    }
                }
                else if($elem2=="") $content .= $key2.' = '.PHP_EOL;
                else $content .= $key2.' = "'.$elem2.'"'.PHP_EOL;
            }
        }

        if (file_put_contents($this->filePath, $content) === false) {
            throw new Exception('Unable to write forgeupgrade configuration');
        }
    }

}

?>
