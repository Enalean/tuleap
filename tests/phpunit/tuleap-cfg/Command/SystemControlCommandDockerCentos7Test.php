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

namespace TuleapCfg\Command;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SystemControlCommandDockerCentos7Test extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var MockInterface|ProcessFactory
     */
    private $process_factory;
    private $control_command;
    /**
     * @var CommandTester
     */
    private $command_tester;

    /**
     * @var vfsStreamDirectory
     */
    private $root;

    protected function setUp() : void
    {
        $this->root            = vfsStream::setup('slash');
        $this->process_factory = \Mockery::mock(ProcessFactory::class);
        $this->control_command = new SystemControlCommand($this->process_factory, $this->root->url());
        $this->command_tester  = new CommandTester($this->control_command);

        putenv('TLP_SYSTEMCTL=docker-centos7');
        mkdir($this->root->url() . '/etc/cron.d', 0755, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('TLP_SYSTEMCTL');
    }

    public function testItStartsTuleapCronttab()
    {
        $this->command_tester->execute(['action' => 'start', 'targets' => ['tuleap']]);

        $this->assertFileEquals(__DIR__ . '/../../../../src/utils/cron.d/codendi', $this->root->url() . '/etc/cron.d/tuleap');
        $this->assertEquals(0644, $this->root->getChild('etc/cron.d/tuleap')->getPermissions());
        $this->assertEquals(vfsStream::OWNER_ROOT, $this->root->getChild('etc/cron.d/tuleap')->getUser());

        $this->assertEquals("Starting tuleap...\nOK\n", $this->command_tester->getDisplay());
        $this->assertEquals(0, $this->command_tester->getStatusCode());
    }

    public function testItStopsTuleapCronttab()
    {
        $this->command_tester->execute(['action' => 'stop', 'targets' => ['tuleap']]);

        $this->assertFileEquals(__DIR__ . '/../../../../src/utils/cron.d/codendi-stop', $this->root->url() . '/etc/cron.d/tuleap');
        $this->assertEquals(0644, $this->root->getChild('etc/cron.d/tuleap')->getPermissions());
        $this->assertEquals(vfsStream::OWNER_ROOT, $this->root->getChild('etc/cron.d/tuleap')->getUser());

        $this->assertEquals("Stopping tuleap...\nOK\n", $this->command_tester->getDisplay());
        $this->assertEquals(0, $this->command_tester->getStatusCode());
    }

    public function testItRestartTuleapCronttab()
    {
        $this->command_tester->execute(['action' => 'restart', 'targets' => ['tuleap']]);

        $this->assertFileEquals(__DIR__ . '/../../../../src/utils/cron.d/codendi', $this->root->url() . '/etc/cron.d/tuleap');

        $this->assertEquals("Restarting tuleap...\nOK\n", $this->command_tester->getDisplay());
        $this->assertEquals(0, $this->command_tester->getStatusCode());
    }

    public function testEnableOnDockerIsSameAsStart()
    {
        $this->command_tester->execute(['action' => 'enable', 'targets' => ['tuleap']]);

        $this->assertFileEquals(__DIR__ . '/../../../../src/utils/cron.d/codendi', $this->root->url() . '/etc/cron.d/tuleap');

        $this->assertEmpty($this->command_tester->getDisplay());
        $this->assertEquals(0, $this->command_tester->getStatusCode());
    }

    public function testTuleapCronIsEnabled()
    {
        copy(__DIR__ . '/../../../../src/utils/cron.d/codendi', $this->root->url() . '/etc/cron.d/tuleap');

        $this->command_tester->execute(['action' => 'is-enabled', 'targets' => ['tuleap']]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
    }

    public function testTuleapCronIsDisabled()
    {
        copy(__DIR__ . '/../../../../src/utils/cron.d/codendi-stop', $this->root->url() . '/etc/cron.d/tuleap');

        $this->command_tester->execute(['action' => 'is-enabled', 'targets' => ['tuleap']]);

        $this->assertEquals(1, $this->command_tester->getStatusCode());
    }

    /**
     *
     * @testWith [ "mask" ]
     *           [ "is-active"]
     */
    public function testDoingNothingWithActions(string $action)
    {
        $this->command_tester->execute(['action' => $action, 'targets' => ['tuleap']], ['capture_stderr_separately' => true]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());
        $this->assertEmpty($this->command_tester->getErrorOutput());
    }

    public function testTuleapWithOtherCommands()
    {
        $this->command_tester->execute(['action' => 'start', 'targets' => ['httpd', 'tuleap', 'nginx']]);

        $this->assertFileEquals(__DIR__ . '/../../../../src/utils/cron.d/codendi', $this->root->url() . '/etc/cron.d/tuleap');
        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEquals("Starting tuleap...\nOK\nDoing nothing with start httpd, nginx...\nOK\n", $this->command_tester->getDisplay());
    }
}
