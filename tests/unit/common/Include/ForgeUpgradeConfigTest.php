<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
 *
 */

class ForgeUpgradeConfigTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\TemporaryTestDirectory;

    private $fixtures;
    private $command;
    /**
     * @var string
     */
    private $config_file;
    /**
     * @var ForgeUpgradeConfig
     */
    private $forgeupgrade_config;

    public function setUp(): void
    {
        parent::setUp();
        $this->fixtures = $this->getTmpDir();
        $source      = escapeshellarg(__DIR__ . '/_fixtures');
        $destination = escapeshellarg($this->fixtures);
        exec("cp -a $source/* $destination/");
        $this->command = \Mockery::spy(\System_Command::class);
    }

    public function testPluginPathIsInConfig()
    {
        $fuc = new ForgeUpgradeConfig($this->command, $this->fixtures . '/forgeupgrade-config-docman.ini');
        $this->assertTrue($fuc->existsInPath('/usr/share/tuleap/plugins/docman'));
        $this->assertFalse($fuc->existsInPath('/usr/share/tuleap/plugins/git'));
    }

    public function testAddPathInFile()
    {
        copy($this->fixtures . '/forgeupgrade-config-docman.ini', $this->fixtures . '/forgeupgrade-addpath.ini');

        $fuc = new ForgeUpgradeConfig($this->command, $this->fixtures . '/forgeupgrade-addpath.ini');
        $this->assertFalse($fuc->existsInPath('/usr/share/tuleap/plugins/git'));

        $fuc->addPath('/usr/share/tuleap/plugins/git');
        $this->assertTrue($fuc->existsInPath('/usr/share/tuleap/plugins/git'));

        // Verify by loading it again
        $fuc2 = new ForgeUpgradeConfig($this->command, $this->fixtures . '/forgeupgrade-addpath.ini');
        $this->assertTrue($fuc2->existsInPath('/usr/share/tuleap/plugins/git'));

        unlink($this->fixtures . '/forgeupgrade-addpath.ini');
    }

    public function testRemovePathAtTheEndOfFile()
    {
        copy($this->fixtures . '/forgeupgrade-config-docman.ini', $this->fixtures . '/forgeupgrade-addpath.ini');

        $fuc = new ForgeUpgradeConfig($this->command, $this->fixtures . '/forgeupgrade-addpath.ini');
        $this->assertTrue($fuc->existsInPath('/usr/share/tuleap/plugins/docman'));

        $fuc->removePath('/usr/share/tuleap/plugins/docman');
        $this->assertFalse($fuc->existsInPath('/usr/share/tuleap/plugins/docman'));

        // Verify by loading it again
        $fuc2 = new ForgeUpgradeConfig($this->command, $this->fixtures . '/forgeupgrade-addpath.ini');
        $this->assertFalse($fuc2->existsInPath('/usr/share/tuleap/plugins/docman'));

        unlink($this->fixtures . '/forgeupgrade-addpath.ini');
    }

    public function testRemovePathInTheMiddleOfFile()
    {
        $configFile = $this->fixtures . '/forgeupgrade-addpath.ini';
        copy($this->fixtures . '/forgeupgrade-config-docman.ini', $configFile);

        $fuc = new ForgeUpgradeConfig($this->command, $configFile);
        $this->assertTrue($fuc->existsInPath('/usr/share/tuleap/plugins/webdav'));

        $fuc->removePath('/usr/share/tuleap/plugins/webdav');
        $this->assertFalse($fuc->existsInPath('/usr/share/tuleap/plugins/webdav'));

        // Verify by loading it again
        $fuc2 = new ForgeUpgradeConfig($this->command, $configFile);
        $this->assertFalse($fuc2->existsInPath('/usr/share/tuleap/plugins/webdav'));

        unlink($configFile);
    }

    public function testItRecordsOnlyThePathOfThePlugin()
    {
        $this->command = \Mockery::spy(\System_Command::class);
        $this->forgeupgrade_config = new ForgeUpgradeConfig($this->command, dirname(__FILE__) . '/_fixtures/forgeupgrade-config-docman.ini');
        $this->command->shouldReceive('exec')->with("/usr/lib/forgeupgrade/bin/forgeupgrade --dbdriver='/usr/share/tuleap/src/forgeupgrade/ForgeUpgrade_Db_Driver_Codendi.php' --path='/usr/share/tuleap/plugins/agiledashboard' record-only")->once();

        $this->forgeupgrade_config->recordOnlyPath('/usr/share/tuleap/plugins/agiledashboard');
    }

    public function testItCallsForgeUpgrade()
    {
        $this->command             = \Mockery::spy(\System_Command::class);
        $this->config_file         = dirname(__FILE__) . '/_fixtures/forgeupgrade-config-docman.ini';
        $this->forgeupgrade_config = new ForgeUpgradeConfig($this->command, $this->config_file);

        $this->command->shouldReceive('exec')->with("/usr/lib/forgeupgrade/bin/forgeupgrade --config='{$this->config_file}' 'check-update'")->once()->andReturns(array());

        $this->forgeupgrade_config->isSystemUpToDate();
    }

    public function testItReturnsTrueWhenForgeUpgradeTellsThatSystemIsUpToDate()
    {
        $this->command             = \Mockery::spy(\System_Command::class);
        $this->config_file         = dirname(__FILE__) . '/_fixtures/forgeupgrade-config-docman.ini';
        $this->forgeupgrade_config = new ForgeUpgradeConfig($this->command, $this->config_file);

        $this->command->shouldReceive('exec')->andReturns(array(
            '[32mINFO - System up-to-date',
            '[0m',
        ));

        $this->assertTrue($this->forgeupgrade_config->isSystemUpToDate());
    }

    public function testItReturnsFalseWhenForgeUpgradeTellsThereArePendingBuckets()
    {
        $this->command             = \Mockery::spy(\System_Command::class);
        $this->config_file         = dirname(__FILE__) . '/_fixtures/forgeupgrade-config-docman.ini';
        $this->forgeupgrade_config = new ForgeUpgradeConfig($this->command, $this->config_file);

        $this->command->shouldReceive('exec')->andReturns(array(
            '/usr/share/tuleap/plugins/tracker/db/mysql/updates/2015/201510131648_add_emailgateway_column_to_tracker.php',
            'Add enable_emailgateway column to tracker table',
            '1 migrations pending',
        ));

        $this->assertFalse($this->forgeupgrade_config->isSystemUpToDate());
    }
}
