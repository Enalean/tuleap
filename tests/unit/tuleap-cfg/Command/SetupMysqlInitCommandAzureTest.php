<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace TuleapCfg\Command;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\ForgeConfigSandbox;
use TuleapCfg\Command\SetupMysql\DatabaseConfigurator;
use TuleapCfg\Command\SetupMysql\DBWrapperInterface;

/**
 * @covers \TuleapCfg\Command\SetupMysql\DatabaseConfigurator
 */
final class SetupMysqlInitCommandAzureTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var string
     */
    private $base_dir;
    /**
     * @var CommandTester
     */
    private $command_tester;
    /**
     * @var DBWrapperInterface
     */
    private $db_wrapper;

    protected function setUp(): void
    {
        $this->base_dir = vfsStream::setup()->url();
        mkdir($this->base_dir . '/etc/tuleap/conf', 0750, true);

        \ForgeConfig::set('sys_custom_dir', $this->base_dir . '/etc/tuleap');

        $this->db_wrapper = new TestDBWrapper();

        $test_manager         = new TestConnectionManager($this->db_wrapper);
        $this->command_tester = new CommandTester(
            new SetupMysqlInitCommand(
                $test_manager,
                new DatabaseConfigurator(\PasswordHandlerFactory::getPasswordHandler(), $test_manager),
                $this->base_dir
            )
        );
    }

    protected function tearDown(): void
    {
        putenv('TULEAP_DB_AZURE_SUFFIX');
    }

    public function testItWritesConfigurationFileUserWithMiddleAt(): void
    {
        $this->command_tester->execute([
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--app-user'       => 'tuleap',
            '--azure-suffix'   => 'some-id',
            '--tuleap-fqdn'    => 'localhost',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());

        $this->assertFileExists($this->base_dir . '/etc/tuleap/conf/database.inc');
        require($this->base_dir . '/etc/tuleap/conf/database.inc');
        $this->assertEquals('tuleap@some-id', $sys_dbuser);
    }

    public function testItWritesConfigurationFileUserFromEnv(): void
    {
        putenv('TULEAP_DB_AZURE_SUFFIX=some-id');
        $this->command_tester->execute([
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--app-user'       => 'tuleap',
            '--tuleap-fqdn'    => 'localhost',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());

        $this->assertFileExists($this->base_dir . '/etc/tuleap/conf/database.inc');
        require($this->base_dir . '/etc/tuleap/conf/database.inc');
        $this->assertEquals('tuleap@some-id', $sys_dbuser);
    }

    public function testGrantTuleapAccessApplicationUserWithMiddleAt(): void
    {
        $this->command_tester->execute([
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--app-user'       => 'tuleap',
            '--azure-suffix'   => 'some-id',
            '--tuleap-fqdn'    => 'localhost',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());

        $this->assertContains("CREATE USER IF NOT EXISTS 'tuleap'@'%' IDENTIFIED BY 'a complex password'", $this->db_wrapper->statements);
        $this->assertContains("GRANT ALL PRIVILEGES ON 'tuleap'.* TO 'tuleap'@'%'", $this->db_wrapper->statements);
    }

    public function testGrantMediawikiPerProjectAccessToApplicationUserWithMiddleAt(): void
    {
        $this->command_tester->execute([
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--app-user'       => 'tuleap',
            '--mediawiki'      => 'per-project',
            '--azure-suffix'   => 'some-id',
            '--tuleap-fqdn'    => 'localhost',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());

        $this->db_wrapper->assertContains("GRANT ALL PRIVILEGES ON `plugin_mediawiki_%`.* TO 'tuleap'@'%'");
    }

    public function testGrantMediawikiCentralAccessToApplicationUserWithMiddleAt(): void
    {
        $this->command_tester->execute([
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--app-user'       => 'tuleap',
            '--mediawiki'      => 'central',
            '--azure-suffix'   => 'some-id',
            '--tuleap-fqdn'    => 'localhost',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());

        $this->db_wrapper->assertContains("GRANT ALL PRIVILEGES ON 'tuleap_mediawiki'.* TO 'tuleap'@'%'");
    }
}
