<?php
/*
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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

class RenameProjectTest extends TuleapTestCase
{
    public function testRenameProjectTest()
    {
        $docman_root = $this->getTmpDir();
        $old_name = 'toto';
        $new_name = 'TestProj';
        mkdir($docman_root.$old_name);

        $project = \Mockery::spy(Project::class);
        $project->allows()->getUnixName(true)->andReturns($old_name);

        $fact = \Mockery::mock(Docman_VersionFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertEqual(rename($docman_root.$old_name, $docman_root.$new_name), true);

        $dao = \Mockery::spy(Docman_VersionDao::class);
        $fact->allows(['_getVersionDao' => $dao]);
        $dao->allows()->renameProject($docman_root, $project, $new_name)->andReturns(true);

        $this->assertFalse(is_dir($docman_root.$old_name), "Docman old rep should be renamed");
        $this->assertTrue(is_dir($docman_root.$new_name), "Docman new Rep should be created");
    }
}
