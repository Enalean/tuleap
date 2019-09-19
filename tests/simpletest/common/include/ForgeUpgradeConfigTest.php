<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

class ForgeUpgradeConfigTest extends TuleapTestCase
{
    private $fixtures;
    private $command;

    public function setUp()
    {
        parent::setUp();
        $this->fixtures = $this->getTmpDir();
        $source      = escapeshellarg(dirname(__FILE__).'/_fixtures');
        $destination = escapeshellarg($this->fixtures);
        exec("cp -a $source/* $destination/");
        $this->command = mock('System_Command');
    }

    public function testPluginPathIsInConfig()
    {
        $fuc = new ForgeUpgradeConfig($this->command, $this->fixtures.'/forgeupgrade-config-docman.ini');
        $this->assertTrue($fuc->existsInPath('/usr/share/tuleap/plugins/docman'));
        $this->assertFalse($fuc->existsInPath('/usr/share/tuleap/plugins/git'));
    }

    public function testAddPathInFile()
    {
        copy($this->fixtures.'/forgeupgrade-config-docman.ini', $this->fixtures.'/forgeupgrade-addpath.ini');

        $fuc = new ForgeUpgradeConfig($this->command, $this->fixtures.'/forgeupgrade-addpath.ini');
        $this->assertFalse($fuc->existsInPath('/usr/share/tuleap/plugins/git'));

        $fuc->addPath('/usr/share/tuleap/plugins/git');
        $this->assertTrue($fuc->existsInPath('/usr/share/tuleap/plugins/git'));

        // Verify by loading it again
        $fuc2 = new ForgeUpgradeConfig($this->command, $this->fixtures.'/forgeupgrade-addpath.ini');
        $this->assertTrue($fuc2->existsInPath('/usr/share/tuleap/plugins/git'));

        unlink($this->fixtures.'/forgeupgrade-addpath.ini');
    }

    public function testRemovePathAtTheEndOfFile()
    {
        copy($this->fixtures.'/forgeupgrade-config-docman.ini', $this->fixtures.'/forgeupgrade-addpath.ini');

        $fuc = new ForgeUpgradeConfig($this->command, $this->fixtures.'/forgeupgrade-addpath.ini');
        $this->assertTrue($fuc->existsInPath('/usr/share/tuleap/plugins/docman'));

        $fuc->removePath('/usr/share/tuleap/plugins/docman');
        $this->assertFalse($fuc->existsInPath('/usr/share/tuleap/plugins/docman'));

        // Verify by loading it again
        $fuc2 = new ForgeUpgradeConfig($this->command, $this->fixtures.'/forgeupgrade-addpath.ini');
        $this->assertFalse($fuc2->existsInPath('/usr/share/tuleap/plugins/docman'));

        unlink($this->fixtures.'/forgeupgrade-addpath.ini');
    }

    public function testRemovePathInTheMiddleOfFile()
    {
        $configFile = $this->fixtures.'/forgeupgrade-addpath.ini';
        copy($this->fixtures.'/forgeupgrade-config-docman.ini', $configFile);

        $fuc = new ForgeUpgradeConfig($this->command, $configFile);
        $this->assertTrue($fuc->existsInPath('/usr/share/tuleap/plugins/webdav'));

        $fuc->removePath('/usr/share/tuleap/plugins/webdav');
        $this->assertFalse($fuc->existsInPath('/usr/share/tuleap/plugins/webdav'));

        // Verify by loading it again
        $fuc2 = new ForgeUpgradeConfig($this->command, $configFile);
        $this->assertFalse($fuc2->existsInPath('/usr/share/tuleap/plugins/webdav'));

        unlink($configFile);
    }
}

class ForgeUpgradeConfig_InstallPluginTest extends TuleapTestCase
{
    private $command;
    private $forgeupgrade_config;

    public function setUp()
    {
        parent::setUp();
        $this->command = mock('System_Command');
        $this->forgeupgrade_config = new ForgeUpgradeConfig($this->command, dirname(__FILE__).'/_fixtures/forgeupgrade-config-docman.ini');
    }

    public function itRecordsOnlyThePathOfThePlugin()
    {
        expect($this->command)->exec("/usr/lib/forgeupgrade/bin/forgeupgrade --dbdriver='/usr/share/tuleap/src/forgeupgrade/ForgeUpgrade_Db_Driver_Codendi.php' --path='/usr/share/tuleap/plugins/agiledashboard' record-only")->once();

        $this->forgeupgrade_config->recordOnlyPath('/usr/share/tuleap/plugins/agiledashboard');
    }
}

class ForgeUpgradeConfig_IsSystemUpToDateTest extends TuleapTestCase
{
    private $command;
    private $forgeupgrade_config;
    private $config_file;

    public function setUp()
    {
        parent::setUp();
        $this->command             = mock('System_Command');
        $this->config_file         = dirname(__FILE__).'/_fixtures/forgeupgrade-config-docman.ini';
        $this->forgeupgrade_config = new ForgeUpgradeConfig($this->command, $this->config_file);
    }

    public function itCallsForgeUpgrade()
    {
        expect($this->command)->exec("/usr/lib/forgeupgrade/bin/forgeupgrade --config='{$this->config_file}' 'check-update'")->once();
        stub($this->command)->exec()->returns(array());

        $this->forgeupgrade_config->isSystemUpToDate();
    }

    public function itReturnsTrueWhenForgeUpgradeTellsThatSystemIsUpToDate()
    {
        stub($this->command)->exec()->returns(
            array(
                '[32mINFO - System up-to-date',
                '[0m',
            )
        );

        $this->assertTrue($this->forgeupgrade_config->isSystemUpToDate());
    }

    public function itReturnsFalseWhenForgeUpgradeTellsThereArePendingBuckets()
    {
        stub($this->command)->exec()->returns(
            array(
                '/usr/share/tuleap/plugins/tracker/db/mysql/updates/2015/201510131648_add_emailgateway_column_to_tracker.php',
                'Add enable_emailgateway column to tracker table',
                '1 migrations pending',
            )
        );

        $this->assertFalse($this->forgeupgrade_config->isSystemUpToDate());
    }
}
