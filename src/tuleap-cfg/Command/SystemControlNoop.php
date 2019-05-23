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

class SystemControlNoop implements SystemControlInterface
{
    /**
     * @var string
     */
    private $action;
    /**
     * @var string[]
     */
    private $targets;

    public function __construct(string $action, string ...$targets)
    {
        $this->action = $action;
        $this->targets = $targets;
    }

    public function getBeforeMessage(): string
    {
        return sprintf('Doing nothing with %s %s...', $this->action, implode(', ', $this->targets));
    }

    public function run(): void
    {
    }

    public function isSuccessful(): bool
    {
        return true;
    }

    public function getExitCode(): int
    {
        return 0;
    }

    public function getOutput(): string
    {
        return '';
    }

    public function getCommandLine(): string
    {
        return '';
    }

    public function getErrorOutput(): string
    {
        return '';
    }
}
