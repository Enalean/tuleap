<?php
/*
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
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

require_once 'bootstrap.php';

Mock::generatePartial('Docman_VersionFactory','Docman_VersionFactoryTestVersion2', array('_getVersionDao',));

Mock::generate('Docman_VersionDao');

Mock::generate('Project');

class RenameProjectTest extends TuleapTestCase {

    function testRenameProjectTest() {

        $rem  = new Docman_VersionFactoryTestVersion2($this);

        $docman_root = dirname(__FILE__) . '/../tests/_fixtures/docman/';
        $old_name = 'toto';
        $new_name = 'TestProj';
        mkdir ($docman_root.$old_name);

        $project = new MockProject($this);
        $project->setReturnValue('getUnixName', $old_name, array(true));

        $fact = new Docman_VersionFactoryTestVersion2($this);
        $this->assertEqual(rename($docman_root.$old_name, $docman_root.$new_name), true);

        $dao = new MockDocman_VersionDao($fact);
        $fact->setReturnValue('_getVersionDao', $dao);
        $dao->setReturnValue('renameProject', true, array($docman_root, $project, $new_name));


        $this->assertFalse(is_dir($docman_root.$old_name), "Docman old rep should be renamed");
        $this->assertTrue(is_dir($docman_root.$new_name), "Docman new Rep should be created");

        rmdir($docman_root."/".$new_name);

   }
}
?>
