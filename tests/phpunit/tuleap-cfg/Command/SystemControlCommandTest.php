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
use Symfony\Component\Process\Process;

class SystemControlCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MockInterface|Process
     */
    private $process;
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

    protected function setUp(): void
    {
        $this->root            = vfsStream::setup('slash');
        $this->process         = \Mockery::mock(Process::class, ['isSuccessful' => true, 'getExitCode' => 0]);
        $this->process_factory = \Mockery::mock(ProcessFactory::class);
        $this->control_command = new SystemControlCommand($this->process_factory, $this->root->url());
        $this->command_tester  = new CommandTester($this->control_command);
    }

    public function testItStartsNginxWithSystemD()
    {
        $this->process->shouldReceive('run')->once();

        $this->process_factory->shouldReceive('getProcess')->with(['/usr/bin/systemctl', 'start', 'nginx'])->andReturns(
            $this->process
        );

        $this->command_tester->execute(['action'  => 'start', 'targets'  => ['nginx']]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $output = $this->command_tester->getDisplay();
        $this->assertEquals("Starting nginx...\nOK\n", $output);
    }

    public function testItGetsAFailureOnCommandRun()
    {
        $this->process->shouldReceive('run');
        $this->process->shouldReceive('isSuccessful')->andReturns(false);
        $this->process->shouldReceive('getExitCode')->andReturns(255);
        $this->process->shouldReceive('getOutput')->andReturns('some error');
        $this->process->shouldReceive('getErrorOutput')->andReturns('');
        $this->process->shouldReceive('getCommandLine')->andReturns('/usr/bin/stuff');

        $this->process_factory->shouldReceive('getProcess')->andReturns($this->process);

        $this->command_tester->execute(['action' => 'start', 'targets'  => ['nginx']], ['capture_stderr_separately' => true]);

        $this->assertEquals(255, $this->command_tester->getStatusCode());
        $this->assertEquals("Starting nginx...\n", $this->command_tester->getDisplay());
        $this->assertEquals("Error while running `/usr/bin/stuff`\nsome error\n", $this->command_tester->getErrorOutput());
    }

    public function testItDoesntAddAFinalCRLFWhenAlreadyOnOutput()
    {
        $this->process->shouldReceive('run');
        $this->process->shouldReceive('isSuccessful')->andReturns(false);
        $this->process->shouldReceive('getExitCode')->andReturns(255);
        $this->process->shouldReceive('getOutput')->andReturns("some error\n");
        $this->process->shouldReceive('getErrorOutput')->andReturns('');
        $this->process->shouldReceive('getCommandLine')->andReturns('/usr/bin/stuff');

        $this->process_factory->shouldReceive('getProcess')->andReturns($this->process);

        $this->command_tester->execute(['action' => 'start', 'targets'  => ['nginx']], ['capture_stderr_separately' => true]);

        $this->assertEquals(255, $this->command_tester->getStatusCode());
        $this->assertEquals("Starting nginx...\n", $this->command_tester->getDisplay());
        $this->assertEquals("Error while running `/usr/bin/stuff`\nsome error\n", $this->command_tester->getErrorOutput());
    }

    public function testItGetsAFailureWithoutOutput()
    {
        $this->process->shouldReceive('run');
        $this->process->shouldReceive('isSuccessful')->andReturns(false);
        $this->process->shouldReceive('getExitCode')->andReturns(255);
        $this->process->shouldReceive('getOutput')->andReturns('');
        $this->process->shouldReceive('getErrorOutput')->andReturns('');
        $this->process->shouldReceive('getCommandLine')->andReturns('/usr/bin/stuff');

        $this->process_factory->shouldReceive('getProcess')->andReturns($this->process);

        $this->command_tester->execute(['action' => 'start', 'targets'  => ['nginx']], ['capture_stderr_separately' => true]);

        $this->assertEquals(255, $this->command_tester->getStatusCode());
        $this->assertEquals("Starting nginx...\n", $this->command_tester->getDisplay());
        $this->assertEquals("Error while running `/usr/bin/stuff` without output\n", $this->command_tester->getErrorOutput());
    }

    public function testItGetsAnOutputOnStderr()
    {
        $this->process->shouldReceive('run');
        $this->process->shouldReceive('isSuccessful')->andReturns(false);
        $this->process->shouldReceive('getExitCode')->andReturns(255);
        $this->process->shouldReceive('getOutput')->andReturns('');
        $this->process->shouldReceive('getErrorOutput')->andReturns('foo');
        $this->process->shouldReceive('getCommandLine')->andReturns('/usr/bin/stuff');

        $this->process_factory->shouldReceive('getProcess')->andReturns($this->process);

        $this->command_tester->execute(['action' => 'start', 'targets'  => ['nginx']], ['capture_stderr_separately' => true]);

        $this->assertEquals(255, $this->command_tester->getStatusCode());
        $this->assertEquals("Starting nginx...\n", $this->command_tester->getDisplay());
        $this->assertEquals("Error while running `/usr/bin/stuff`\nfoo\n", $this->command_tester->getErrorOutput());
    }

    public function testItGetsAnOutputOnStdoutAndStderr()
    {
        $this->process->shouldReceive('run');
        $this->process->shouldReceive('isSuccessful')->andReturns(false);
        $this->process->shouldReceive('getExitCode')->andReturns(255);
        $this->process->shouldReceive('getOutput')->andReturns('some stuff');
        $this->process->shouldReceive('getErrorOutput')->andReturns('another foo');
        $this->process->shouldReceive('getCommandLine')->andReturns('/usr/bin/stuff');

        $this->process_factory->shouldReceive('getProcess')->andReturns($this->process);

        $this->command_tester->execute(['action' => 'start', 'targets'  => ['nginx']], ['capture_stderr_separately' => true]);

        $this->assertEquals(255, $this->command_tester->getStatusCode());
        $this->assertEquals("Starting nginx...\n", $this->command_tester->getDisplay());
        $this->assertEquals("Error while running `/usr/bin/stuff`\nGot on stdout:\nsome stuff\nGot on stderr:\nanother foo\n", $this->command_tester->getErrorOutput());
    }

    public function testItStopsApache()
    {
        $this->process->shouldReceive('run')->once();

        $this->process_factory->shouldReceive('getProcess')->with(['/usr/bin/systemctl', 'stop', 'httpd'])->andReturns(
            $this->process
        );

        $this->command_tester->execute(['action' => 'stop', 'targets' => ['httpd']]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $output = $this->command_tester->getDisplay();
        $this->assertEquals("Stopping httpd...\nOK\n", $output);
    }

    public function testItThrowsAnErrorWhenActionIsNotKnown()
    {
        $this->process_factory->shouldNotReceive('getProcess');

        $this->command_tester->execute(['action' => 'foo', 'targets' => ['httpd']], ['capture_stderr_separately' => true]);

        $this->assertEquals(1, $this->command_tester->getStatusCode());
        $this->assertEquals("`foo` is not a valid action\n", $this->command_tester->getErrorOutput());
    }

    /**
     *
     * @testWith [ "mask" ]
     *           [ "enable" ]
     *           [ "is-active"]
     *           [ "is-enabled" ]
     */
    public function testCommandDoesntOutputAnything(string $action)
    {
        $this->process->shouldReceive('run');
        $this->process_factory
            ->shouldReceive('getProcess')
            ->with(['/usr/bin/systemctl', '--quiet', $action, 'httpd'])
            ->andReturns($this->process);

        $this->command_tester->execute(['action' => $action, 'targets' => ['httpd']], ['capture_stderr_separately' => true]);

        $this->assertEquals(0, $this->command_tester->getStatusCode());
        $this->assertEmpty($this->command_tester->getDisplay());
        $this->assertEmpty($this->command_tester->getErrorOutput());
    }

    public function testItExecuteActionOnSeveralTargets()
    {
        $this->process->shouldReceive('run');
        $this->process_factory
            ->shouldReceive('getProcess')
            ->with(['/usr/bin/systemctl', 'start', 'httpd', 'nginx'])
            ->andReturns($this->process);

        $this->command_tester->execute(['action' => 'start', 'targets' => ['httpd', 'nginx']]);
    }
}
