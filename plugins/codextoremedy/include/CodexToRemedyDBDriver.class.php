<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 *
 *
 * ForgeUpgrade is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ForgeUpgrade is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ForgeUpgrade. If not, see <http://www.gnu.org/licenses/>.
 */

class CodexToRemedyDBDriver {
    protected $pdo;
    protected $dsn;
    protected $user;
    protected $password;

    public function __construct() {
        if ($this->getProperty('db_host') && $this->getProperty('db_name')&& $this->getProperty('db_port')) {
            $this->dsn      = 'oci:dbname='.$this->getProperty('db_host').':'.$this->getProperty('db_port').'/'.$this->getProperty('db_name');
            $this->user     = $this->getProperty('db_user');
            $this->password = $this->getProperty('db_passwd');
        } else {
            throw new Exception('Unable to find valid parameters connexion, please check codextoremedy conf file');
        }
    }

    /**
     * Setup the PDO object to be used for DB connexion
     *
     * The DB connexion will be used to insert tickets in RIF remedy DB.
     *
     * @return PDO
     */
    public function getPdo() {
        if (!$this->pdo) {
            $this->pdo = new PDO($this->dsn, $this->user, $this->password);
        }
        return $this->pdo;
    }

    /**
     * Retreive a param config giving its name
     *
     * @param String $name
     *
     * @return String
     */
    public function getProperty($name) {
        $pluginManager = PluginManager::instance();
        $p = $pluginManager->getPluginByName('codextoremedy');
        $info =$p->getPluginInfo();
        return $info->getPropertyValueForName($name);
    }

}

?>