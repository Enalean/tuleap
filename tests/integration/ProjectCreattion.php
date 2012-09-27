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

class ProjectCreationTest extends TuleapTestCase {
    public function itCreatesAProject() {
        mysql_connect("localhost", "codendiadm", "pAdK-1?2");
        mysql_query("DROP DATABASE IF EXISTS integration_test;");
        mysql_query("CREATE DATABASE integration_test;");
        `mysql -ucodendiadm -ppAdK-1?2 integration_test < src/db/mysql/database_structure.sql`;
        
        $GLOBALS['sys_dbhost'] = 'localhost';
        $GLOBALS['sys_dbuser'] = 'codendiadm';
        $GLOBALS['sys_dbpasswd'] = 'pAdK-1?2';
        $GLOBALS['sys_dbname'] = 'integration_test';
        
        $projectCreator = new ProjectCreator(ProjectManager::instance(), new Rule_ProjectName(), new Rule_ProjectFullName());
        $projectCreator->create('toto', 'tata', array());
        
    }
}
?>
