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

use Symfony\Component\Process\Process;

class SystemControlSystemd implements SystemControlInterface
{
    private const SYSTEMCTL = '/usr/bin/systemctl';

    /**
     * @var Process
     */
    private $process;
    /**
     * @var string
     */
    private $action;
    /**
     * @var string[]
     */
    private $targets;

    public function __construct(ProcessFactory $process_factory, bool $quiet, string $action, string ...$targets)
    {
        if ($quiet) {
            $command = array_merge([self::SYSTEMCTL, '--quiet', $action], $targets);
        } else {
            $command = array_merge([self::SYSTEMCTL, $action], $targets);
        }
        $this->process = $process_factory->getProcess($command);
        $this->targets = $targets;
        $this->action  = $action;
    }

    public function run(): void
    {
        $this->process->run();
    }

    public function isSuccessful(): bool
    {
        return $this->process->isSuccessful();
    }

    public function getExitCode(): int
    {
        $exit_code = $this->process->getExitCode();
        if ($exit_code === null) {
            throw new \LogicException('Do not attempt to get exit code while the process is still running');
        }
        return $exit_code;
    }

    public function getOutput(): string
    {
        return $this->process->getOutput();
    }

    public function getCommandLine(): string
    {
        return $this->process->getCommandLine();
    }

    public function getBeforeMessage(): string
    {
        return sprintf('%s %s...', SystemControlCommand::ACTION_WORD[$this->action], implode(', ', $this->targets));
    }

    public function getErrorOutput(): string
    {
        return $this->process->getErrorOutput();
    }
}
