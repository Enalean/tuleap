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
require_once 'common/project/ProjectCreator.class.php';
require_once 'common/dao/CodendiDataAccess.class.php';
require_once 'database.php';
require_once 'exit.php';
require_once 'html.php';
require_once 'user.php';

class ProjectCreationTest extends TuleapTestCase {

    // GRANT ALL PRIVILEGES on integration_test.* to 'integration_test'@'localhost' identified by 'welcome0';
    public function setUp() {
        parent::setUp();

        $GLOBALS['sys_dbhost']   = 'localhost';
        $GLOBALS['sys_dbuser']   = 'integration_test';
        $GLOBALS['sys_dbpasswd'] = 'welcome0';
        $GLOBALS['sys_dbname']   = 'integration_test';
        
        mysql_connect($GLOBALS['sys_dbhost'], $GLOBALS['sys_dbuser'], $GLOBALS['sys_dbpasswd']);
        mysql_query("DROP DATABASE IF EXISTS integration_test;");
        mysql_query("CREATE DATABASE integration_test;");

        $mysql_cmd = 'mysql -u'.$GLOBALS['sys_dbuser'].' -p'.$GLOBALS['sys_dbpasswd'].' '.$GLOBALS['sys_dbname'];
        
        $cmd = $mysql_cmd.' < src/db/mysql/database_structure.sql';
        system($cmd);
        $cmd = $mysql_cmd.' < src/db/mysql/database_initvalues.sql';
        system($cmd);
        
        $GLOBALS['feedback'] = '';
        db_connect();
    }

    public function itCreatesAProject() {
        $projectCreator = new ProjectCreator(ProjectManager::instance(), new Rule_ProjectName(), new Rule_ProjectFullName());
        $projectCreator->create('short-name', 'Long name', array(
            'project' => array(
                'form_license'           => 'xrx',
                'form_license_other'     => '',
                'form_short_description' => '',
                'is_test'                => false,
                'is_public'              => false,
                'services'               => array(),
                'built_from_template'    => 100,
            )
        ));
        
        $res = db_query('SELECT * FROM groups WHERE unix_group_name="short-name"');
        $row = db_fetch_array($res);
        $this->assertEqual($row['group_name'], 'Long name');
    }
}
?>
