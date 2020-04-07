<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace TuleapCfg\Command\ConfigureCommand;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\Console\Tester\CommandTester;

class ConfigureCommandTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var vfsStreamDirectory
     */
    private $root;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('slash');
    }

    public function testItSkipsApache()
    {
        $command = new \TuleapCfg\Command\ConfigureCommand(vfsStream::url('slash'));
        $command_tester = new CommandTester($command);
        $command_tester->execute([
            'module'  => 'apache',
        ]);

        $this->assertEquals(0, $command_tester->getStatusCode());
        $output = $command_tester->getDisplay();
        $this->assertStringContainsString('Nothing to do', $output);
        $this->assertStringNotContainsString('Apache has been configured', $output);
        $this->assertStringNotContainsString('Apache is already configured', $output);
    }

    public function testHTTPConf()
    {
        $base = vfsStream::url('slash');
        mkdir($base . '/etc/httpd/conf', 0777, true);
        copy(__DIR__ . '/../../../../src/tuleap-cfg/resources/httpd.conf', $base . '/etc/httpd/conf/httpd.conf');
        $command = new \TuleapCfg\Command\ConfigureCommand($base);
        $command_tester = new CommandTester($command);
        $command_tester->execute([
            'module'  => 'apache',
        ]);

        $this->assertEquals(0, $command_tester->getStatusCode());
        $this->assertEquals(file_get_contents(__DIR__ . '/_fixtures/httpd.conf'), file_get_contents($base . '/etc/httpd/conf/httpd.conf'));
        $this->assertStringContainsString('Apache has been configured', $command_tester->getDisplay());
    }

    public function testSSLConf()
    {
        $base = vfsStream::url('slash');
        mkdir($base . '/etc/httpd/conf.d', 0777, true);
        copy(__DIR__ . '/../../../../src/tuleap-cfg/resources/ssl.conf', $base . '/etc/httpd/conf.d/ssl.conf');
        $command = new \TuleapCfg\Command\ConfigureCommand($base);
        $command_tester = new CommandTester($command);
        $command_tester->execute([
            'module'  => 'apache',
        ]);

        $this->assertEquals(0, $command_tester->getStatusCode());
        $this->assertEquals(file_get_contents(__DIR__ . '/_fixtures/ssl.conf'), file_get_contents($base . '/etc/httpd/conf.d/ssl.conf'));
        $this->assertStringContainsString('Apache has been configured', $command_tester->getDisplay());
    }

    public function testAlreadyConfigured()
    {
        $base = vfsStream::url('slash');
        mkdir($base . '/etc/httpd/conf', 0777, true);
        mkdir($base . '/etc/httpd/conf.d', 0777, true);
        copy(__DIR__ . '/_fixtures/httpd.conf', $base . '/etc/httpd/conf/httpd.conf');
        copy(__DIR__ . '/_fixtures/ssl.conf', $base . '/etc/httpd/conf.d/ssl.conf');
        $command = new \TuleapCfg\Command\ConfigureCommand($base);
        $command_tester = new CommandTester($command);
        $command_tester->execute([
            'module'  => 'apache',
        ]);

        $this->assertEquals(0, $command_tester->getStatusCode());
        $this->assertStringContainsString('Apache is already configured', $command_tester->getDisplay());
    }

    public function testNoWriteAccessOnHTTPDConf()
    {
        $base = vfsStream::url('slash');

        $etc = vfsStream::newDirectory('/etc/httpd/conf', 0755);
        $this->root->addChild($etc);

        $file = vfsStream::newFile('httpd.conf', 0444)->setContent("foo");
        $file->chown(vfsStream::OWNER_ROOT);
        $file->chgrp(vfsStream::GROUP_ROOT);

        $conf_dir = $this->root->getChild('etc/httpd/conf');
        $conf_dir->addChild($file);

        $command = new \TuleapCfg\Command\ConfigureCommand($base);
        $command_tester = new CommandTester($command);
        $command_tester->execute([
            'module'  => 'apache',
        ]);

        $this->assertEquals(1, $command_tester->getStatusCode());

        $output = $command_tester->getDisplay();
        $this->assertStringContainsString('not writable', $output);
        $this->assertStringNotContainsString('Apache has been configured', $output);
        $this->assertStringNotContainsString('Apache is already configured', $output);
    }

    public function testNoWriteAccessOnSSLConf()
    {
        $base = vfsStream::url('slash');

        $etc = vfsStream::newDirectory('/etc/httpd/conf.d', 0755);
        $this->root->addChild($etc);

        $file = vfsStream::newFile('ssl.conf', 0444)->setContent("foo");
        $file->chown(vfsStream::OWNER_ROOT);
        $file->chgrp(vfsStream::GROUP_ROOT);

        $conf_dir = $this->root->getChild('etc/httpd/conf.d');
        $conf_dir->addChild($file);

        $command = new \TuleapCfg\Command\ConfigureCommand($base);
        $command_tester = new CommandTester($command);
        $command_tester->execute([
            'module'  => 'apache',
        ]);

        $this->assertEquals(1, $command_tester->getStatusCode());

        $output = $command_tester->getDisplay();
        $this->assertStringContainsString('not writable', $output);
        $this->assertStringNotContainsString('Apache has been configured', $output);
        $this->assertStringNotContainsString('Apache is already configured', $output);
    }
}
