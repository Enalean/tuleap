<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

require_once 'common/include/ForgeUpgradeConfig.class.php';

Mock::generate('Plugin');

class ForgeUpgradeConfigTest extends UnitTestCase {
    protected $fixtures;

    public function __construct() {
        $this->fixtures = dirname(__FILE__).'/_fixtures';
    }

    public function testPluginPathIsInConfig() {
        $fuc = new ForgeUpgradeConfig($this->fixtures.'/forgeupgrade-config-docman.ini');
        $this->assertTrue($fuc->existsInPath('/usr/share/codendi/plugins/docman'));
        $this->assertFalse($fuc->existsInPath('/usr/share/codendi/plugins/git'));
    }

    public function testAddPathInFile() {
        copy($this->fixtures.'/forgeupgrade-config-docman.ini', $this->fixtures.'/forgeupgrade-addpath.ini');

        $fuc = new ForgeUpgradeConfig($this->fixtures.'/forgeupgrade-addpath.ini');
        $this->assertFalse($fuc->existsInPath('/usr/share/codendi/plugins/git'));

        $fuc->addPath('/usr/share/codendi/plugins/git');
        $this->assertTrue($fuc->existsInPath('/usr/share/codendi/plugins/git'));

        // Verify by loading it again
        $fuc2 = new ForgeUpgradeConfig($this->fixtures.'/forgeupgrade-addpath.ini');
        $this->assertTrue($fuc2->existsInPath('/usr/share/codendi/plugins/git'));

        unlink($this->fixtures.'/forgeupgrade-addpath.ini');
    }

    public function testRemovePathInFile() {
        copy($this->fixtures.'/forgeupgrade-config-docman.ini', $this->fixtures.'/forgeupgrade-addpath.ini');

        $fuc = new ForgeUpgradeConfig($this->fixtures.'/forgeupgrade-addpath.ini');
        $this->assertTrue($fuc->existsInPath('/usr/share/codendi/plugins/docman'));

        $fuc->removePath('/usr/share/codendi/plugins/docman');
        $this->assertFalse($fuc->existsInPath('/usr/share/codendi/plugins/docman'));

        // Verify by loading it again
        $fuc2 = new ForgeUpgradeConfig($this->fixtures.'/forgeupgrade-addpath.ini');
        $this->assertFalse($fuc2->existsInPath('/usr/share/codendi/plugins/docman'));

        unlink($this->fixtures.'/forgeupgrade-addpath.ini');
    }
}


?>