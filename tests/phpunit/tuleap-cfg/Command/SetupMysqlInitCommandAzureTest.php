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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TuleapCfg\Command\SetupMysql\DBWrapperInterface;

final class SetupMysqlInitCommandAzureTest extends TestCase
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
        copy(__DIR__ . '/../../../../src/etc/local.inc.dist', $this->base_dir . '/etc/tuleap/conf/local.inc');

        $this->db_wrapper = new TestDBWrapper();

        $this->command_tester = new CommandTester(
            new SetupMysqlInitCommand(
                new TestConnectionManager($this->db_wrapper),
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
            '--skip-database'  => true,
            '--admin-password' => 'welcome0',
            '--nss-password'   => 'another complex password',
            '--azure-suffix'   => 'some-id',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());

        $this->assertFileDoesNotExist($this->base_dir . '/etc/tuleap/conf/database.inc');

        require($this->base_dir . '/etc/tuleap/conf/local.inc');
        $this->assertEquals('dbauthuser@some-id', $sys_dbauth_user);
        $this->assertEquals('another complex password', $sys_dbauth_passwd);
    }
}
