<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */
require_once 'common/dao/CodendiDataAccess.class.php';
require_once 'database.php';

class DBTestAccess {
    protected $mysqli;

    public function __construct() {
        $this->loadConfiguration();
        $this->mysqli = mysqli_init();
        if (! $this->mysqli->real_connect($GLOBALS['sys_dbhost'], $GLOBALS['sys_dbuser'], $GLOBALS['sys_dbpasswd'])) {
            $this->mysqli = false;
        }
    }

    public function setUp() {
        Config::set('DEBUG_MODE', true);

        $this->initDb();
        $this->mysqli->select_db(Config::get('sys_dbname'));
        db_connect();
    }

    protected function truncateTable($table) {
        $this->mysqli->query("TRUNCATE TABLE $table");
    }

    /**
     * Execute all statements of given file (bulk imports)
     *
     * @param String $file
     */
    protected function mysqlLoadFile($file) {
        $mysql_cmd = 'mysql -h '.$GLOBALS['sys_dbhost'].' -u'.$GLOBALS['sys_dbuser'].' -p'.$GLOBALS['sys_dbpasswd'].' '.$GLOBALS['sys_dbname'];
        $cmd = $mysql_cmd.' < '.$file;
        system($cmd);
    }

    private function loadConfiguration() {
        $config_file = 'tests.inc';
        Config::load(dirname(__FILE__)."/../../src/etc/$config_file.dist");
        Config::load(dirname($this->getLocalIncPath())."/$config_file");
        $GLOBALS['sys_dbhost']   = Config::get('sys_dbhost');
        $GLOBALS['sys_dbuser']   = Config::get('sys_dbuser');
        $GLOBALS['sys_dbpasswd'] = Config::get('sys_dbpasswd');
        $GLOBALS['sys_dbname']   = Config::get('sys_dbname');
    }

    private function getLocalIncPath() {
        return getenv('CODENDI_LOCAL_INC') ? getenv('CODENDI_LOCAL_INC') : '/etc/codendi/conf/local.inc';
    }

    private function foraceCreateDatabase() {
        $this->mysqli->query("DROP DATABASE IF EXISTS ".$GLOBALS['sys_dbname']);
        $this->mysqli->query("CREATE DATABASE ".$GLOBALS['sys_dbname']);
    }

    private function initDb() {
        $this->foraceCreateDatabase();
        $this->mysqlLoadFile('src/db/mysql/database_structure.sql');
        $this->mysqlLoadFile('src/db/mysql/database_initvalues.sql');
        $this->mysqlLoadFile('src/db/mysql/trackerv3values.sql');
        $this->mysqlLoadFile('plugins/tracker_date_reminder/db/install.sql');
        $this->mysqlLoadFile('plugins/tracker_date_reminder/db/examples.sql');
        $this->mysqlLoadFile('plugins/graphontrackers/db/install.sql');
        $this->mysqlLoadFile('plugins/graphontrackers/db/initvalues.sql');
        $this->mysqlLoadFile('plugins/tracker/db/install.sql');
        $this->mysqlLoadFile('plugins/graphontrackersv5/db/install.sql');
    }
}
?>