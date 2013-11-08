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

    public function __construct() {
        $this->loadConfiguration();
    }

    public function setUp() {
        Config::set('DEBUG_MODE', true);

        db_connect();
    }

    protected function truncateTable($table) {
        $this->mysqli->query("TRUNCATE TABLE $table");
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
}
