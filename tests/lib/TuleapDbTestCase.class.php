<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

require_once 'common/dao/CodendiDataAccess.class.php';
require_once 'database.php';

// GRANT ALL PRIVILEGES on integration_test.* to 'integration_test'@'localhost' identified by 'welcome0';
abstract class TuleapDbTestCase extends TuleapTestCase {
    protected $mysqli;

    protected static $db_initialized = false;

    private $development_on_going = false;

    private $src_dir = '';

    public function __construct() {
        parent::__construct();
        $this->loadConfiguration();
        $this->mysqli = mysqli_init();
        if (!$this->mysqli->real_connect($GLOBALS['sys_dbhost'], $GLOBALS['sys_dbuser'], $GLOBALS['sys_dbpasswd'])) {
            $this->mysqli = false;
        }
        $this->src_dir = realpath(dirname(__FILE__)."/../..");
    }

    public function setUp() {
        parent::setUp();
        ForgeConfig::set('DEBUG_MODE', true);
        if (self::$db_initialized == false) {
            self::$db_initialized = true;
            $this->initDb();
        }
        $this->mysqli->select_db($GLOBALS['sys_dbname']);
    }

    public function skip() {
        parent::skip();
        if (!$this->mysqli) {
            echo "== (屮ಠ益ಠ)屮 Y U NO CONFIGURE DATABASE? ==\n";
        }
        $this->skipUnless($this->mysqli, '== (屮ಠ益ಠ)屮 Y U NO CONFIGURE DATABASE? ==');
    }

    public function tearDown() {
        unset($GLOBALS['sys_dbhost']);
        unset($GLOBALS['sys_dbuser']);
        unset($GLOBALS['sys_dbpasswd']);
        unset($GLOBALS['sys_dbname']);
        parent::tearDown();
    }

    /**
     * Use this method to flag your test as 'under development'
     * This will prevent drop of the database before tests and avoid become crasy
     * waiting for 50" test execution.
     */
    protected function markThisTestUnderDevelopment() {
        self::$db_initialized       = true;
        $this->development_on_going = true;
    }

    protected function thisTestIsNotUnderDevelopment() {
        return !$this->development_on_going;
    }

    /**
     * Use this method if you need to drop the database after a test
     */
    protected function resetDatabase() {
        self::$db_initialized = false;
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
        $mysql_cmd = 'mysql -u'.$GLOBALS['sys_dbuser'].' -p'.$GLOBALS['sys_dbpasswd'].' '.$GLOBALS['sys_dbname'];
        $cmd = $mysql_cmd.' < '.$this->src_dir.'/'.$file;
        system($cmd);
    }

    private function loadConfiguration() {
        $config_file = 'tests.inc';
        ForgeConfig::loadFromFile(dirname(__FILE__)."/../../src/etc/$config_file.dist");
        ForgeConfig::loadFromFile(dirname($this->getLocalIncPath())."/$config_file");
        $GLOBALS['sys_dbhost']   = ForgeConfig::get('sys_dbhost');
        $GLOBALS['sys_dbuser']   = ForgeConfig::get('sys_dbuser');
        $GLOBALS['sys_dbpasswd'] = ForgeConfig::get('sys_dbpasswd');
        $GLOBALS['sys_dbname']   = ForgeConfig::get('sys_dbname');
    }

    private function getLocalIncPath() {
        return getenv('CODENDI_LOCAL_INC') ? getenv('CODENDI_LOCAL_INC') : '/etc/codendi/conf/local.inc';
    }

    private function foraceCreateDatabase() {
        $this->mysqli->query("DROP DATABASE IF EXISTS ".$GLOBALS['sys_dbname']);
        $this->mysqli->query("CREATE DATABASE ".$GLOBALS['sys_dbname']);
    }

    protected function initDb() {
        $this->foraceCreateDatabase();
        $this->mysqlLoadFile('src/db/mysql/database_structure.sql');
        $this->mysqlLoadFile('src/db/mysql/database_initvalues.sql');
        $this->mysqlLoadFile('src/db/mysql/trackerv3structure.sql');
        $this->mysqlLoadFile('src/db/mysql/trackerv3values.sql');
        $this->mysqlLoadFile('plugins/tracker_date_reminder/db/install.sql');
        $this->mysqlLoadFile('plugins/tracker_date_reminder/db/examples.sql');
        $this->mysqlLoadFile('plugins/graphontrackers/db/install.sql');
        $this->mysqlLoadFile('plugins/graphontrackers/db/initvalues.sql');
        $this->mysqlLoadFile('plugins/tracker/db/install.sql');
        $this->mysqlLoadFile('plugins/graphontrackersv5/db/install.sql');
        $this->mysqlLoadFile('plugins/statistics/db/install.sql');
    }
}

?>
