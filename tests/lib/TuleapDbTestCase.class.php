<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

    protected $db_initialized = false;

    public function setUp() {
        Config::set('DEBUG_MODE', true);
        parent::setUp();
        $GLOBALS['sys_dbhost']   = 'localhost';
        $GLOBALS['sys_dbuser']   = 'integration_test';
        $GLOBALS['sys_dbpasswd'] = 'welcome0';
        $GLOBALS['sys_dbname']   = 'integration_test';
        if ($this->db_initialized == false) {
            $this->initDb();
            $this->db_initialized = true;
            db_connect();
        }
    }

    public function tearDown() {
        parent::tearDown();
        unset($GLOBALS['sys_dbhost']);
        unset($GLOBALS['sys_dbuser']);
        unset($GLOBALS['sys_dbpasswd']);
        unset($GLOBALS['sys_dbname']);
    }

    protected function mysqlLoadFile($file) {
        $mysql_cmd = 'mysql -u'.$GLOBALS['sys_dbuser'].' -p'.$GLOBALS['sys_dbpasswd'].' '.$GLOBALS['sys_dbname'];
        $cmd = $mysql_cmd.' < '.$file;
        system($cmd);
    }

    protected function resetDb() {
        mysql_connect($GLOBALS['sys_dbhost'], $GLOBALS['sys_dbuser'], $GLOBALS['sys_dbpasswd']);
        mysql_query("DROP DATABASE IF EXISTS integration_test");
        mysql_query("CREATE DATABASE integration_test");
    }

    protected function initDb() {
        $this->resetDb();
        $this->mysqlLoadFile('src/db/mysql/database_structure.sql');
        $this->mysqlLoadFile('src/db/mysql/database_initvalues.sql');
        $this->mysqlLoadFile('src/db/mysql/trackerv3values.sql');
    }

    protected function dumpDb() {
        $cmd = 'mysqldump --opt -u'.$GLOBALS['sys_dbuser'].' -p'.$GLOBALS['sys_dbpasswd'].' '.$GLOBALS['sys_dbname'].' > empty_db.sql';
        system($cmd);
    }

    protected function loadDbDump() {
        $this->resetDb();
        $this->mysqlLoadFile('empty_db.sql');
    }

}

?>
