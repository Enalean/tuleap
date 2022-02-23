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
use Tuleap\DB\DBAuthUserConfig;
use TuleapCfg\Command\SetupMysql\DatabaseConfigurator;
use TuleapCfg\Command\SetupMysql\DBWrapperInterface;

/**
 * @covers \TuleapCfg\Command\SetupMysql\DatabaseConfigurator
 */
final class SetupMysqlInitCommandAzureTest extends \Tuleap\Test\PHPUnit\TestCase
{
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
            '--skip-database'  => true,
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--app-user'       => 'tuleap',
            '--azure-suffix'   => 'some-id',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());

        $this->assertFileExists($this->base_dir . '/etc/tuleap/conf/database.inc');
        require($this->base_dir . '/etc/tuleap/conf/database.inc');
        $this->assertEquals('tuleap@some-id', $sys_dbuser);
    }

    public function testItWritesConfigurationFileUserFromEnv(): void
    {
        putenv('TULEAP_DB_AZURE_SUFFIX=some-id');
        $this->command_tester->execute([
            '--skip-database'  => true,
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--app-user'       => 'tuleap',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());

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
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());

        $this->assertContains("CREATE USER IF NOT EXISTS 'tuleap'@'%' IDENTIFIED BY 'a complex password'", $this->db_wrapper->statements);
        $this->assertContains("GRANT ALL PRIVILEGES ON 'tuleap'.* TO 'tuleap'@'%'", $this->db_wrapper->statements);
    }

    public function testGrantNssUserWithMiddleAt(): void
    {
        $this->command_tester->execute([
            '--admin-password' => 'welcome0',
            '--nss-password'   => 'another complex password',
            '--nss-user'       => 'dbauthuser',
            '--azure-suffix'   => 'some-id',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());

        $this->db_wrapper->assertContains("CREATE USER IF NOT EXISTS 'dbauthuser'@'%' IDENTIFIED BY 'another complex password'");
        $this->db_wrapper->assertContains("GRANT CREATE,SELECT ON 'tuleap'.'user' TO 'dbauthuser'@'%'");
    }

    public function testGrantMediawikiPerProjectAccessToApplicationUserWithMiddleAt(): void
    {
        $this->command_tester->execute([
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--app-user'       => 'tuleap',
            '--mediawiki'      => 'per-project',
            '--azure-suffix'   => 'some-id',
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
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());

        $this->db_wrapper->assertContains("GRANT ALL PRIVILEGES ON 'tuleap_mediawiki'.* TO 'tuleap'@'%'");
    }

    public function testItWritesDBAuthUserCredentials(): void
    {
        $this->command_tester->execute([
            '--admin-password' => 'welcome0',
            '--nss-password'   => 'another complex password',
            '--azure-suffix'   => 'some-id',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());

        $first_insert_pos = array_search("REPLACE INTO 'tuleap'.forgeconfig (name, value) VALUES (?, ?)", $this->db_wrapper->statements, true);
        self::assertEquals(DBAuthUserConfig::USER, $this->db_wrapper->statements_params[$first_insert_pos][0]);
        self::assertEquals('dbauthuser@some-id', $this->db_wrapper->statements_params[$first_insert_pos][1]);

        self::assertEquals(DBAuthUserConfig::PASSWORD, $this->db_wrapper->statements_params[$first_insert_pos + 1][0]);
        self::assertNotEmpty($this->db_wrapper->statements_params[$first_insert_pos + 1][1]);
    }
}
