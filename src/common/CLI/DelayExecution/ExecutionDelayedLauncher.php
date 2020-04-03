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
 */

declare(strict_types=1);

namespace Tuleap\CLI\DelayExecution;

final class ExecutionDelayedLauncher
{
    /**
     * @var ExecutionDelayer
     */
    private $execution_delayer;

    public function __construct(ExecutionDelayer $execution_delayer)
    {
        $this->execution_delayer = $execution_delayer;
    }

    /**
     * @psalm-param callable: void $execution
     */
    public function execute(callable $execution): void
    {
        $this->execution_delayer->delay();
        $execution();
    }
}
