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

final class ExecutionDelayerRandomizedSleep implements ExecutionDelayer
{
    /**
     * @psalm-var positive-int|0
     */
    private int $max_randomization_delay;

    /**
     * @psalm-param positive-int|0 $max_randomization_delay
     */
    public function __construct(int $max_randomization_delay)
    {
        $this->max_randomization_delay = $max_randomization_delay;
    }

    #[\Override]
    public function delay(): void
    {
        $sleep_time = random_int(0, $this->max_randomization_delay);
        sleep($sleep_time);
    }
}
