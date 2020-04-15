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
use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;
use TuleapCfg\Command\SetupMysql\ConnectionManagerInterface;

final class SetupMysqlInitCommandTest extends TestCase
{
    /**
     * @var string
     */
    private $base_dir;
    /**
     * @var CommandTester
     */
    private $command_tester;

    protected function setUp(): void
    {
        vfsStream::setup('slash');
        $this->base_dir = vfsStream::url('slash');
        mkdir($this->base_dir . '/etc/tuleap/conf', 0750, true);
        mkdir($this->base_dir . '/etc/pki/ca-trust/extracted/pem/', 0750, true);
        touch($this->base_dir . '/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem');
        touch($this->base_dir . '/some_ca.pem');

        $connection_manager = new class implements ConnectionManagerInterface {

            public function getDBWithoutDBName(
                SymfonyStyle $io,
                string $host,
                int $port,
                string $ssl_mode,
                string $ssl_ca_file,
                string $user,
                string $password
            ): ?EasyDB {
                return null;
            }

            public function checkSQLModes(EasyDB $db): void
            {
            }
        };
        $this->command_tester = new CommandTester(
            new SetupMysqlInitCommand(
                $connection_manager,
                $this->base_dir
            )
        );
    }

    protected function tearDown(): void
    {
        putenv('TULEAP_DB_SSL_MODE');
        putenv('TULEAP_DB_SSL_CA');
    }

    public function testItWritesConfigurationFileWithGivenValuesNoSSL(): void
    {
        $this->command_tester->execute([
            '--skip-database'  => true,
            '--host'           => '1.2.3.4',
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());

        $this->assertFileExists($this->base_dir . '/etc/tuleap/conf/database.inc');
        require($this->base_dir . '/etc/tuleap/conf/database.inc');
        $this->assertEquals('1.2.3.4', $sys_dbhost);
        $this->assertEquals('tuleap', $sys_dbname);
        $this->assertEquals('tuleapadm', $sys_dbuser);
        $this->assertEquals('a complex password', $sys_dbpasswd);

        $this->assertEquals('0', $sys_enablessl);
        $this->assertEquals('/etc/pki/ca-trust/extracted/pem/tls-ca-bundle.pem', $sys_db_ssl_ca);
        $this->assertEquals('0', $sys_db_ssl_verify_cert);
    }

    public function testItWritesConfigurationFileUserWithoutMiddleAt(): void
    {
        $this->command_tester->execute([
            '--skip-database'  => true,
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--app-user'       => 'tuleap@%'
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());

        $this->assertFileExists($this->base_dir . '/etc/tuleap/conf/database.inc');
        require($this->base_dir . '/etc/tuleap/conf/database.inc');
        $this->assertEquals('tuleap', $sys_dbuser);
    }

    public function testItWritesConfigurationFileWithGivenValuesEnableSSL(): void
    {
        $this->command_tester->execute([
            '--skip-database'  => true,
            '--host'           => '1.2.3.4',
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
            '--ssl-mode'       => 'verify-ca',
            '--ssl-ca'         => '/some_ca.pem',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());

        $this->assertFileExists($this->base_dir . '/etc/tuleap/conf/database.inc');
        require($this->base_dir . '/etc/tuleap/conf/database.inc');
        $this->assertEquals('1.2.3.4', $sys_dbhost);
        $this->assertEquals('tuleap', $sys_dbname);
        $this->assertEquals('tuleapadm', $sys_dbuser);
        $this->assertEquals('a complex password', $sys_dbpasswd);

        $this->assertEquals('1', $sys_enablessl);
        $this->assertEquals('/some_ca.pem', $sys_db_ssl_ca);
        $this->assertEquals('1', $sys_db_ssl_verify_cert);
    }

    public function testNotConfigurationFileWrittenIfPasswordNotProvided(): void
    {
        $this->command_tester->execute([
            '--skip-database'  => true,
            '--host'           => '1.2.3.4',
            '--app-password'   => 'a complex password',
        ]);

        $this->assertEquals(1, $this->command_tester->getStatusCode());
        $this->assertFileDoesNotExist($this->base_dir . '/etc/tuleap/conf/database.inc');
    }

    public function testUsesSSLModeDefinedWithEnvVariable(): void
    {
        putenv('TULEAP_DB_SSL_MODE=no-verify');

        $this->command_tester->execute([
            '--skip-database'  => true,
            '--host'           => '1.2.3.4',
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());

        require($this->base_dir . '/etc/tuleap/conf/database.inc');

        $this->assertEquals('1', $sys_enablessl);
    }

    public function testUsesSSLCAFileDefinedWithEnvVariable(): void
    {
        putenv('TULEAP_DB_SSL_MODE=no-verify');
        putenv('TULEAP_DB_SSL_CA=/some_ca.pem');

        $this->command_tester->execute([
            '--skip-database'  => true,
            '--host'           => '1.2.3.4',
            '--admin-password' => 'welcome0',
            '--app-password'   => 'a complex password',
        ]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());

        require($this->base_dir . '/etc/tuleap/conf/database.inc');

        $this->assertEquals('/some_ca.pem', $sys_db_ssl_ca);
    }
}
