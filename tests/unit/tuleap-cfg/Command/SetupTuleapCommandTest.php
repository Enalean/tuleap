<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;
use Tuleap\Config\ConfigDao;
use Tuleap\Cryptography\SecretKeyFile;
use Tuleap\ForgeConfigSandbox;
use Tuleap\ForgeUpgrade\ForgeUpgradeRecordOnly;
use Tuleap\Test\PHPUnit\TestCase;

final class SetupTuleapCommandTest extends TestCase
{
    use ForgeConfigSandbox;

    private string $base_dir;
    private CommandTester $command_tester;
    private \PHPUnit\Framework\MockObject\MockObject|ProcessFactory $process_factory;
    private SecretKeyFile $key_factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_dir = vfsStream::setup()->url();
        mkdir($this->base_dir . '/etc/tuleap/conf', 0750, true);
        $this->process_factory = $this->createMock(ProcessFactory::class);
        $this->process_factory->method('getProcessWithoutTimeout')->willReturn(new Process(['/bin/true']));

        $dao = $this->createMock(ConfigDao::class);
        $dao->method('searchAll')->willReturn([]);
        \ForgeConfig::setDatabaseConfigDao($dao);

        $this->key_factory = new class implements SecretKeyFile {
            public bool $key_created     = false;
            public bool $permissions_set = false;

            public function initAndGetEncryptionKeyPath(): string
            {
                $this->key_created = true;
                return '';
            }

            public function restoreOwnership(LoggerInterface $logger): void
            {
                $this->permissions_set = true;
            }
        };

        $forge_upgrade = new class implements ForgeUpgradeRecordOnly {
            public function recordOnlyCore(): void
            {
            }
        };

        $this->command_tester = new CommandTester(
            new SetupTuleapCommand($this->process_factory, $this->key_factory, fn () => $forge_upgrade, $this->base_dir)
        );
    }

    public function testItWritesTuleapConfigFile(): void
    {
        $this->command_tester->execute(['--tuleap-fqdn' => 'tuleap.example.com']);
        self::assertEquals(0, $this->command_tester->getStatusCode());

        self::assertFileExists($this->base_dir . '/etc/tuleap/conf/local.inc');
        require $this->base_dir . '/etc/tuleap/conf/local.inc';

        self::assertEquals('tuleap.example.com', $sys_default_domain);
        self::assertEquals('codendi-admin@tuleap.example.com', $sys_email_admin);
        self::assertEquals('codendi-contact@tuleap.example.com', $sys_email_contact);
        self::assertEquals('"Tuleap" <noreply@tuleap.example.com>', $sys_noreply);
        self::assertEquals('Tuleap', $sys_org_name);
        self::assertEquals('Tuleap', $sys_long_org_name);
        self::assertSame('0', $sys_mail_secure_mode);
        self::assertSame('1', $sys_disable_subdomains);
    }

    public function testItDoesntModifyAnExistingLocalInc(): void
    {
        $local_inc = $this->base_dir . '/etc/tuleap/conf/local.inc';
        file_put_contents($local_inc, 'foo');

        $this->command_tester->execute(['--tuleap-fqdn' => 'tuleap.example.com']);
        self::assertEquals(0, $this->command_tester->getStatusCode());

        self::assertStringEqualsFile($local_inc, 'foo');
    }

    public function testItBackupsAndOverrideExistingLocalIncWithForce(): void
    {
        $local_inc = $this->base_dir . '/etc/tuleap/conf/local.inc';
        file_put_contents($local_inc, 'foo');

        $this->command_tester->execute(['--tuleap-fqdn' => 'tuleap.example.com', '--force' => true]);
        self::assertEquals(0, $this->command_tester->getStatusCode());

        require $this->base_dir . '/etc/tuleap/conf/local.inc';

        self::assertEquals('tuleap.example.com', $sys_default_domain);

        $backup_file_found = false;
        foreach (new \DirectoryIterator($this->base_dir . '/etc/tuleap/conf') as $file) {
            if ($file->getBasename() === 'local.inc') {
                continue;
            }
            if ($file->isFile() && preg_match('/^local\.inc\.\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/', $file->getBasename())) {
                $backup_file_found = $file->getPathname();
            }
        }
        self::assertStringEqualsFile($backup_file_found, 'foo');
    }

    public function testItWritesVariablesWithComments(): void
    {
        $this->command_tester->execute(['--tuleap-fqdn' => 'tuleap.example.com']);
        self::assertEquals(0, $this->command_tester->getStatusCode());

        $full_content = file_get_contents($this->base_dir . '/etc/tuleap/conf/local.inc');

        $needle     = '$sys_default_domain = \'tuleap.example.com\';';
        $pos        = strpos($full_content, $needle);
        $first_part = substr($full_content, 0, $pos + strlen($needle));

        self::assertEquals(
            <<<EOT
        <?php

        // The default Tuleap domain
        //
        // This is used where ever the "naked" form of the Tuleap domain might be used.
        // This is also used as the default name for the Web server using
        // the standard https protocols. You can also define a specific port number (useful for test servers - default 443)
        \$sys_default_domain = 'tuleap.example.com';
        EOT,
            $first_part
        );
    }

    public function testItGenerateSecretKey(): void
    {
        $this->command_tester->execute(['--tuleap-fqdn' => 'tuleap.example.com']);

        self::assertTrue($this->key_factory->key_created);
        self::assertTrue($this->key_factory->permissions_set);
    }
}
