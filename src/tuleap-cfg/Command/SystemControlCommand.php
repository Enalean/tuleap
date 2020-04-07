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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SystemControlCommand extends Command
{
    private const ENV_SYSTEMCTL           = 'TLP_SYSTEMCTL';
    private const ENV_SYSTEMCTL_DOCKER_C7 = 'docker-centos7';
    private const ENV_SYSTEMCTL_C7        = 'centos7';

    private const ALLOWED_ACTIONS = [
        'start',
        'stop',
        'restart',
        'reload',
        'enable',
        'is-active',
        'is-enabled',
        'mask',
    ];

    public const ACTION_WORD = [
        'start'   => 'Starting',
        'stop'    => 'Stopping',
        'restart' => 'Restarting',
        'reload'  => 'Reloading',
    ];

    /**
     * @var ProcessFactory
     */
    private $process_factory;
    /**
     * @var string
     */
    private $base_directory;

    public function __construct(ProcessFactory $process_factory, ?string $base_directory = null)
    {
        $this->process_factory = $process_factory;
        $this->base_directory  = $base_directory !== null ? $base_directory : '/';

        parent::__construct();
    }

    protected function configure(): void
    {
        $help  = "systemctl is a wrapper for commands to init system\n";
        $help .= "\n";
        $help .= "You can modify the command behaviour by setting environment variable TLP_SYSTEMCTL to 'centos7' or 'docker-centos7'";
        $this
            ->setName('systemctl')
            ->setDescription('Wrapper for service activation / desactivation')
            ->addArgument('action', InputArgument::REQUIRED, 'Action to run (start, stop, restart)')
            ->addArgument('targets', InputArgument::IS_ARRAY, 'Services or daemons to work with')
            ->setHelp($help);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action       = $input->getArgument('action');
        assert(is_string($action));
        $targets      = $input->getArgument('targets');
        assert(is_array($targets));

        $error_output = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        if (! in_array($action, self::ALLOWED_ACTIONS, true)) {
            $error_output->writeln(sprintf('`%s` is not a valid action', $action));
            return 1;
        }
        $quiet = ! isset(self::ACTION_WORD[$action]);

        $all_commands = $this->getAllCommands($targets, $action, $quiet);

        foreach ($all_commands as $command) {
            assert($command instanceof SystemControlInterface);
            if (! $quiet) {
                $output->writeln($command->getBeforeMessage());
            }
            $command->run();
            if ($command->isSuccessful()) {
                if (! $quiet) {
                    $output->writeln('OK');
                }
            } else {
                $error_output->write($this->formatErrorMessage($command));
                return $command->getExitCode();
            }
        }

        return 0;
    }

    private function getSystemControlContext(): string
    {
        $env = getenv(self::ENV_SYSTEMCTL);
        if ($env === false) {
            return self::ENV_SYSTEMCTL_C7;
        }
        return strtolower($env) === self::ENV_SYSTEMCTL_DOCKER_C7 ? self::ENV_SYSTEMCTL_DOCKER_C7 : self::ENV_SYSTEMCTL_C7;
    }

    private function getAllCommands(array $targets, string $action, bool $quiet): array
    {
        if ($this->getSystemControlContext() === self::ENV_SYSTEMCTL_DOCKER_C7) {
            $all_commands = [];
            if (in_array(SystemControlTuleapCron::TARGET_NAME, $targets, true)) {
                $all_commands[] = new SystemControlTuleapCron($this->base_directory, $action);
            }
            $other_targets = array_filter($targets, static function (string $target) {
                return $target !== SystemControlTuleapCron::TARGET_NAME;
            });
            if (count($other_targets) > 0) {
                $all_commands[] = new SystemControlNoop($action, ...$other_targets);
            }
            return $all_commands;
        }

        return [
            new SystemControlSystemd($this->process_factory, $quiet, $action, ...$targets)
        ];
    }

    private function formatErrorMessage(SystemControlInterface $command): string
    {
        $error_message = '<error>Error while running `' . $command->getCommandLine() . '`';
        $stdout = $command->getOutput();
        $stderr = $command->getErrorOutput();
        if ($stdout && $stderr) {
            $error_message .= PHP_EOL . 'Got on stdout:' . PHP_EOL;
            $error_message .= $this->addTrailingCRLFWhenMissing($stdout);
            $error_message .= 'Got on stderr:' . PHP_EOL;
            $error_message .= $this->addTrailingCRLFWhenMissing($stderr);
        } elseif ($stderr) {
            $error_message .= PHP_EOL . $this->addTrailingCRLFWhenMissing($stderr);
        } elseif ($stdout) {
            $error_message .= PHP_EOL . $this->addTrailingCRLFWhenMissing($stdout);
        } else {
            $error_message .= ' without output' . PHP_EOL;
        }
        $error_message .= '</error>';

        return $error_message;
    }

    private function addTrailingCRLFWhenMissing(string $string): string
    {
        return substr($string, -1) === PHP_EOL ? $string : $string . PHP_EOL;
    }
}
