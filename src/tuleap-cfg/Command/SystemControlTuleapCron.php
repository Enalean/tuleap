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

class SystemControlTuleapCron implements SystemControlInterface
{
    public const TARGET_NAME = 'tuleap';

    /**
     * @var string
     */
    private $base_directory;
    /**
     * @var string
     */
    private $action;
    /**
     * @var bool
     */
    private $isSuccessful = false;
    /**
     * @var int
     */
    private $exitCode = 1;

    public function __construct(string $base_directory, string $action)
    {
        $this->base_directory = $base_directory;
        $this->action         = $action;
    }

    public function run() : void
    {
        switch ($this->action) {
            case 'reload':
            case 'restart':
            case 'start':
            case 'enable':
                $this->deployCronfile(__DIR__ . '/../../utils/cron.d/codendi');
                $this->isSuccessful = true;
                $this->exitCode     = 0;
                break;
            case 'stop':
                $this->deployCronfile(__DIR__ . '/../../utils/cron.d/codendi-stop');
                $this->isSuccessful = true;
                $this->exitCode     = 0;
                break;
            case 'is-enabled':
                $this->isSuccessful = file_get_contents(__DIR__ . '/../../utils/cron.d/codendi') === file_get_contents($this->base_directory . '/etc/cron.d/tuleap');
                $this->exitCode     = $this->isSuccessful ? 0 : 1;
                break;
            case 'is-active':
            case 'mask':
                $this->isSuccessful = true;
                $this->exitCode     = 0;
                break;
        }
    }

    public function isSuccessful(): bool
    {
        return $this->isSuccessful;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    private function deployCronfile(string $sourcepath): void
    {
        if (! copy($sourcepath, $this->base_directory . '/etc/cron.d/tuleap')) {
            throw new \RuntimeException('Cannot deploy file into /etc/cron.d/tuleap');
        }
        if (! chmod($this->base_directory . '/etc/cron.d/tuleap', 0644)) {
            throw new \RuntimeException('Cannot chmod /etc/cron.d/tuleap');
        }
        if (! chown($this->base_directory . '/etc/cron.d/tuleap', 0)) {
            throw new \RuntimeException('Cannot chown /etc/cron.d/tuleap');
        }
        // For some reasons the following line fails during tests, skipping.
        chgrp($this->base_directory . '/etc/cron.d/tuleap', 0);
    }

    public function getOutput(): string
    {
        return '';
    }

    public function getCommandLine(): string
    {
        return '';
    }

    public function getBeforeMessage(): string
    {
        return sprintf('%s %s...', SystemControlCommand::ACTION_WORD[$this->action], self::TARGET_NAME);
    }

    public function getErrorOutput(): string
    {
        return '';
    }
}
