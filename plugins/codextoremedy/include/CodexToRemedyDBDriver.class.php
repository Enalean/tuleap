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
    protected $dbh;
    protected $dsn;
    protected $user;
    protected $password;

    public function __construct() {
        $pluginManager = PluginManager::instance();
        $p = $pluginManager->getPluginByName('codextoremedy');
        if ($p->getProperty('db_host') && $p->getProperty('db_name')&& $p->getProperty('db_port') && $p->getProperty('db_user') && $p->getProperty('db_passwd')) {
            $this->dsn      = '//'.$p->getProperty('db_host').':'.$p->getProperty('db_port').'/'.$p->getProperty('db_name');
            $this->user     = $p->getProperty('db_user');
            $this->password = $p->getProperty('db_passwd');
        } else {
            throw new Exception('Unable to find valid connexion parameters, please check codextoremedy conf file');
        }
    }

    /**
     * Setup the oci object to be used for DB connexion
     *
     * The DB connexion will be used to insert tickets in RIF remedy DB.
     *
     * @return dbh
     */
    public function getdbh() {
        if (!$this->dbh) {
            $this->dbh = oci_connect($this->user,$this->password,$this->dsn);
        }
        return $this->dbh;
    }

    /**
     * Insert the ticket in RIF DB
     *
     * @param String $summary
     * @param String $description
     * @param String $type
     * @param String $severity
     * @param Date   $createDate
     *
     * @return Boolean
     */
    public function createTicket($summary, $description, $item, $severity, $createDate) {
        $pluginManager = PluginManager::instance();
        $p = $pluginManager->getPluginByName('codextoremedy');
        $submitter = $p->getProperty('codextoremedy_submitter');
        $category  = $p->getProperty('remedy_category');
        $type     = $p->getProperty('remedy_type');

        if ($submitter && $category && $type) {
            $sql = "INSERT INTO RIF_REQUEST
                   (
                   CATEGORY,
                   TYPE,
                   ITEM,
                   REQUESTER_NAME,
                   SUMMARY,
                   DESCRIPTION,
                   SEVERITY,
                   INSERTION_DATE,
                   REQUEST_STATUS,
                   REQUESTER_LOGIN,
                   RIF_ID
                   ) VALUES (
                   '".$category."',
                   '".$type."',
                   '".$item."',
                   '".$submitter."',
                   '".$summary."',
                   '".$description."',
                   '".$severity."',
                   sysdate,
                   'NEW',
                   '".$submitter."',
                   RIF_REQUEST_SEQ.NEXTVAL
                   )";
            $stid = oci_parse($this->dbh , $sql);
            @oci_execute($stid);
            return defined($stid);
        } else {
            return false;
        }
    }
}
?>