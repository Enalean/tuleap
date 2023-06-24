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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

final class SystemControlCommandTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var MockObject&Process
     */
    private $process;
    /**
     * @var MockObject&ProcessFactory
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

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('slash');

        $this->process = $this->getMockBuilder(Process::class)
            ->onlyMethods(['isSuccessful', 'getExitCode', 'run', 'getOutput', 'getErrorOutput', 'getCommandLine'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->process_factory = $this->createMock(ProcessFactory::class);
        $this->control_command = new SystemControlCommand($this->process_factory, $this->root->url());
        $this->command_tester  = new CommandTester($this->control_command);
    }

    public function testItStartsNginxWithSystemD(): void
    {
        $this->process->expects(self::once())->method('run');
        $this->process->method('isSuccessful')->willReturn(true);
        $this->process->method('getExitCode')->willReturn(0);

        $this->process_factory->method('getProcess')->with(['/usr/bin/systemctl', 'start', 'nginx'])->willReturn(
            $this->process
        );

        $this->command_tester->execute(['action'  => 'start', 'targets'  => ['nginx']]);

        self::assertEquals(0, $this->command_tester->getStatusCode());
        $output = $this->command_tester->getDisplay();
        self::assertEquals("Starting nginx...\nOK\n", $output);
    }

    public function testItGetsAFailureOnCommandRun(): void
    {
        $this->process->method('run');
        $this->process->method('isSuccessful')->willReturn(false);
        $this->process->method('getExitCode')->willReturn(255);
        $this->process->method('getOutput')->willReturn('some error');
        $this->process->method('getErrorOutput')->willReturn('');
        $this->process->method('getCommandLine')->willReturn('/usr/bin/stuff');

        $this->process_factory->method('getProcess')->willReturn($this->process);

        $this->command_tester->execute(['action' => 'start', 'targets'  => ['nginx']], ['capture_stderr_separately' => true]);

        self::assertEquals(255, $this->command_tester->getStatusCode());
        self::assertEquals("Starting nginx...\n", $this->command_tester->getDisplay());
        self::assertEquals("Error while running `/usr/bin/stuff`\nsome error\n", $this->command_tester->getErrorOutput());
    }

    public function testItDoesntAddAFinalCRLFWhenAlreadyOnOutput(): void
    {
        $this->process->method('run');
        $this->process->method('isSuccessful')->willReturn(false);
        $this->process->method('getExitCode')->willReturn(255);
        $this->process->method('getOutput')->willReturn("some error\n");
        $this->process->method('getErrorOutput')->willReturn('');
        $this->process->method('getCommandLine')->willReturn('/usr/bin/stuff');

        $this->process_factory->method('getProcess')->willReturn($this->process);

        $this->command_tester->execute(['action' => 'start', 'targets'  => ['nginx']], ['capture_stderr_separately' => true]);

        self::assertEquals(255, $this->command_tester->getStatusCode());
        self::assertEquals("Starting nginx...\n", $this->command_tester->getDisplay());
        self::assertEquals("Error while running `/usr/bin/stuff`\nsome error\n", $this->command_tester->getErrorOutput());
    }

    public function testItGetsAFailureWithoutOutput(): void
    {
        $this->process->method('run');
        $this->process->method('isSuccessful')->willReturn(false);
        $this->process->method('getExitCode')->willReturn(255);
        $this->process->method('getOutput')->willReturn('');
        $this->process->method('getErrorOutput')->willReturn('');
        $this->process->method('getCommandLine')->willReturn('/usr/bin/stuff');

        $this->process_factory->method('getProcess')->willReturn($this->process);

        $this->command_tester->execute(['action' => 'start', 'targets'  => ['nginx']], ['capture_stderr_separately' => true]);

        self::assertEquals(255, $this->command_tester->getStatusCode());
        self::assertEquals("Starting nginx...\n", $this->command_tester->getDisplay());
        self::assertEquals("Error while running `/usr/bin/stuff` without output\n", $this->command_tester->getErrorOutput());
    }

    public function testItGetsAnOutputOnStderr(): void
    {
        $this->process->method('run');
        $this->process->method('isSuccessful')->willReturn(false);
        $this->process->method('getExitCode')->willReturn(255);
        $this->process->method('getOutput')->willReturn('');
        $this->process->method('getErrorOutput')->willReturn('foo');
        $this->process->method('getCommandLine')->willReturn('/usr/bin/stuff');

        $this->process_factory->method('getProcess')->willReturn($this->process);

        $this->command_tester->execute(['action' => 'start', 'targets'  => ['nginx']], ['capture_stderr_separately' => true]);

        self::assertEquals(255, $this->command_tester->getStatusCode());
        self::assertEquals("Starting nginx...\n", $this->command_tester->getDisplay());
        self::assertEquals("Error while running `/usr/bin/stuff`\nfoo\n", $this->command_tester->getErrorOutput());
    }

    public function testItGetsAnOutputOnStdoutAndStderr(): void
    {
        $this->process->method('run');
        $this->process->method('isSuccessful')->willReturn(false);
        $this->process->method('getExitCode')->willReturn(255);
        $this->process->method('getOutput')->willReturn('some stuff');
        $this->process->method('getErrorOutput')->willReturn('another foo');
        $this->process->method('getCommandLine')->willReturn('/usr/bin/stuff');

        $this->process_factory->method('getProcess')->willReturn($this->process);

        $this->command_tester->execute(['action' => 'start', 'targets'  => ['nginx']], ['capture_stderr_separately' => true]);

        self::assertEquals(255, $this->command_tester->getStatusCode());
        self::assertEquals("Starting nginx...\n", $this->command_tester->getDisplay());
        self::assertEquals("Error while running `/usr/bin/stuff`\nGot on stdout:\nsome stuff\nGot on stderr:\nanother foo\n", $this->command_tester->getErrorOutput());
    }

    public function testItStopsApache(): void
    {
        $this->process->expects(self::once())->method('run');
        $this->process->method('isSuccessful')->willReturn(true);
        $this->process->method('getExitCode')->willReturn(0);

        $this->process_factory->method('getProcess')->with(['/usr/bin/systemctl', 'stop', 'httpd'])->willReturn(
            $this->process
        );

        $this->command_tester->execute(['action' => 'stop', 'targets' => ['httpd']]);

        self::assertEquals(0, $this->command_tester->getStatusCode());
        $output = $this->command_tester->getDisplay();
        self::assertEquals("Stopping httpd...\nOK\n", $output);
    }

    public function testItThrowsAnErrorWhenActionIsNotKnown(): void
    {
        $this->process_factory->expects(self::never())->method('getProcess');

        $this->command_tester->execute(['action' => 'foo', 'targets' => ['httpd']], ['capture_stderr_separately' => true]);

        self::assertEquals(1, $this->command_tester->getStatusCode());
        self::assertEquals("`foo` is not a valid action\n", $this->command_tester->getErrorOutput());
    }

    /**
     *
     * @testWith [ "mask" ]
     *           [ "enable" ]
     *           [ "is-active"]
     *           [ "is-enabled" ]
     */
    public function testCommandDoesntOutputAnything(string $action): void
    {
        $this->process->method('run');
        $this->process->method('isSuccessful')->willReturn(true);
        $this->process->method('getExitCode')->willReturn(0);

        $this->process_factory
            ->method('getProcess')
            ->with(['/usr/bin/systemctl', '--quiet', $action, 'httpd'])
            ->willReturn($this->process);

        $this->command_tester->execute(['action' => $action, 'targets' => ['httpd']], ['capture_stderr_separately' => true]);

        self::assertEquals(0, $this->command_tester->getStatusCode());
        self::assertEmpty($this->command_tester->getDisplay());
        self::assertEmpty($this->command_tester->getErrorOutput());
    }

    public function testItExecuteActionOnSeveralTargets(): void
    {
        $this->process->method('run');
        $this->process->method('isSuccessful')->willReturn(true);
        $this->process->method('getExitCode')->willReturn(0);

        $this->process_factory->expects(self::atLeast(1))
            ->method('getProcess')
            ->with(['/usr/bin/systemctl', 'start', 'httpd', 'nginx'])
            ->willReturn($this->process);

        $this->command_tester->execute(['action' => 'start', 'targets' => ['httpd', 'nginx']]);
    }
}
