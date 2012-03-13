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

Mock::generatePartial('ForgeUpgradeConfig', 'ForgeUpgradeConfigTestVersion', array('run'));

class ForgeUpgradeConfigTest extends UnitTestCase {
    protected $fixtures;

    public function __construct() {
        $this->fixtures = dirname(__FILE__).'/_fixtures';
    }

    public function testPluginPathIsInConfig() {
        $fuc = new ForgeUpgradeConfig($this->fixtures.'/forgeupgrade-config-docman.ini');
        $this->assertTrue($fuc->existsInPath('/usr/share/tuleap/plugins/docman'));
        $this->assertFalse($fuc->existsInPath('/usr/share/tuleap/plugins/git'));
    }

    public function testAddPathInFile() {
        copy($this->fixtures.'/forgeupgrade-config-docman.ini', $this->fixtures.'/forgeupgrade-addpath.ini');

        $fuc = new ForgeUpgradeConfig($this->fixtures.'/forgeupgrade-addpath.ini');
        $this->assertFalse($fuc->existsInPath('/usr/share/tuleap/plugins/git'));

        $fuc->addPath('/usr/share/tuleap/plugins/git');
        $this->assertTrue($fuc->existsInPath('/usr/share/tuleap/plugins/git'));

        // Verify by loading it again
        $fuc2 = new ForgeUpgradeConfig($this->fixtures.'/forgeupgrade-addpath.ini');
        $this->assertTrue($fuc2->existsInPath('/usr/share/tuleap/plugins/git'));

        unlink($this->fixtures.'/forgeupgrade-addpath.ini');
    }

    public function testRemovePathAtTheEndOfFile() {
        copy($this->fixtures.'/forgeupgrade-config-docman.ini', $this->fixtures.'/forgeupgrade-addpath.ini');

        $fuc = new ForgeUpgradeConfig($this->fixtures.'/forgeupgrade-addpath.ini');
        $this->assertTrue($fuc->existsInPath('/usr/share/tuleap/plugins/docman'));

        $fuc->removePath('/usr/share/tuleap/plugins/docman');
        $this->assertFalse($fuc->existsInPath('/usr/share/tuleap/plugins/docman'));

        // Verify by loading it again
        $fuc2 = new ForgeUpgradeConfig($this->fixtures.'/forgeupgrade-addpath.ini');
        $this->assertFalse($fuc2->existsInPath('/usr/share/tuleap/plugins/docman'));

        unlink($this->fixtures.'/forgeupgrade-addpath.ini');
    }

    public function testRemovePathInTheMiddleOfFile() {
        $configFile = $this->fixtures.'/forgeupgrade-addpath.ini';
        copy($this->fixtures.'/forgeupgrade-config-docman.ini', $configFile);

        $fuc = new ForgeUpgradeConfig($configFile);
        $this->assertTrue($fuc->existsInPath('/usr/share/tuleap/plugins/webdav'));

        $fuc->removePath('/usr/share/tuleap/plugins/webdav');
        $this->assertFalse($fuc->existsInPath('/usr/share/tuleap/plugins/webdav'));

        // Verify by loading it again
        $fuc2 = new ForgeUpgradeConfig($configFile);
        $this->assertFalse($fuc2->existsInPath('/usr/share/tuleap/plugins/webdav'));

        unlink($configFile);
    }

    public function testForgeUpgradeExecution() {
        $configFile = $this->fixtures.'/forgeupgrade-config-docman.ini';

        $fuc = new ForgeUpgradeConfigTestVersion($this);
        $fuc->setFilePath($configFile);
        $fuc->expectOnce('run', array('/usr/lib/forgeupgrade/bin/forgeupgrade --config=\''.$configFile.'\' \'record-only\''));
        $fuc->setReturnValue('run', true);

        $fuc->execute('record-only');
    }
}


?>