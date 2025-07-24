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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\Console\Tester\CommandTester;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class SystemControlCommandDockerCentos7Test extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProcessFactory
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

    #[\Override]
    protected function setUp(): void
    {
        $this->root            = vfsStream::setup('slash');
        $this->process_factory = $this->createMock(ProcessFactory::class);
        $this->control_command = new SystemControlCommand($this->process_factory, $this->root->url());
        $this->command_tester  = new CommandTester($this->control_command);

        putenv('TLP_SYSTEMCTL=docker');
        mkdir($this->root->url() . '/etc/cron.d', 0755, true);
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('TLP_SYSTEMCTL');
    }

    public function testItStartsTuleapCronttab(): void
    {
        $this->command_tester->execute(['action' => 'start', 'targets' => ['tuleap']]);

        self::assertFileEquals(__DIR__ . '/../../../../src/utils/cron.d/codendi', $this->root->url() . '/etc/cron.d/tuleap');
        self::assertEquals(0644, $this->root->getChild('etc/cron.d/tuleap')->getPermissions());
        self::assertEquals(vfsStream::OWNER_ROOT, $this->root->getChild('etc/cron.d/tuleap')->getUser());

        self::assertEquals("Starting tuleap...\nOK\n", $this->command_tester->getDisplay());
        self::assertEquals(0, $this->command_tester->getStatusCode());
    }

    public function testItStopsTuleapCronttab(): void
    {
        $this->command_tester->execute(['action' => 'stop', 'targets' => ['tuleap']]);

        self::assertFileEquals(__DIR__ . '/../../../../src/utils/cron.d/codendi-stop', $this->root->url() . '/etc/cron.d/tuleap');
        self::assertEquals(0644, $this->root->getChild('etc/cron.d/tuleap')->getPermissions());
        self::assertEquals(vfsStream::OWNER_ROOT, $this->root->getChild('etc/cron.d/tuleap')->getUser());

        self::assertEquals("Stopping tuleap...\nOK\n", $this->command_tester->getDisplay());
        self::assertEquals(0, $this->command_tester->getStatusCode());
    }

    public function testItRestartTuleapCronttab(): void
    {
        $this->command_tester->execute(['action' => 'restart', 'targets' => ['tuleap']]);

        self::assertFileEquals(__DIR__ . '/../../../../src/utils/cron.d/codendi', $this->root->url() . '/etc/cron.d/tuleap');

        self::assertEquals("Restarting tuleap...\nOK\n", $this->command_tester->getDisplay());
        self::assertEquals(0, $this->command_tester->getStatusCode());
    }

    public function testEnableOnDockerIsSameAsStart(): void
    {
        $this->command_tester->execute(['action' => 'enable', 'targets' => ['tuleap']]);

        self::assertFileEquals(__DIR__ . '/../../../../src/utils/cron.d/codendi', $this->root->url() . '/etc/cron.d/tuleap');

        self::assertEmpty($this->command_tester->getDisplay());
        self::assertEquals(0, $this->command_tester->getStatusCode());
    }

    public function testTuleapCronIsEnabled(): void
    {
        copy(__DIR__ . '/../../../../src/utils/cron.d/codendi', $this->root->url() . '/etc/cron.d/tuleap');

        $this->command_tester->execute(['action' => 'is-enabled', 'targets' => ['tuleap']]);

        self::assertEquals(0, $this->command_tester->getStatusCode());
    }

    public function testTuleapCronIsDisabled(): void
    {
        copy(__DIR__ . '/../../../../src/utils/cron.d/codendi-stop', $this->root->url() . '/etc/cron.d/tuleap');

        $this->command_tester->execute(['action' => 'is-enabled', 'targets' => ['tuleap']]);

        self::assertEquals(1, $this->command_tester->getStatusCode());
    }

    /**
     *
     * @testWith [ "mask" ]
     *           [ "is-active"]
     */
    public function testDoingNothingWithActions(string $action): void
    {
        $this->command_tester->execute(['action' => $action, 'targets' => ['tuleap']], ['capture_stderr_separately' => true]);

        self::assertEquals(0, $this->command_tester->getStatusCode());
        self::assertEmpty($this->command_tester->getDisplay());
        self::assertEmpty($this->command_tester->getErrorOutput());
    }

    public function testTuleapWithOtherCommands(): void
    {
        $this->command_tester->execute(['action' => 'start', 'targets' => ['httpd', 'tuleap', 'nginx']]);

        self::assertFileEquals(__DIR__ . '/../../../../src/utils/cron.d/codendi', $this->root->url() . '/etc/cron.d/tuleap');
        self::assertEquals(0, $this->command_tester->getStatusCode());
        self::assertEquals("Starting tuleap...\nOK\nDoing nothing with start httpd, nginx...\nOK\n", $this->command_tester->getDisplay());
    }
}
