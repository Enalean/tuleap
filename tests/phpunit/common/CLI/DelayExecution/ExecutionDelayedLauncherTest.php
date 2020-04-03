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

use PHPUnit\Framework\TestCase;

final class ExecutionDelayedLauncherTest extends TestCase
{
    public function testDelayIsAppliedBeforeRunningTheRestOfTheCode(): void
    {
        $execution_delayer = new class implements ExecutionDelayer {
            public $nb_delay = 0;

            public function delay(): void
            {
                $this->nb_delay++;
            }
        };

        $launcher = new ExecutionDelayedLauncher($execution_delayer);
        $launcher->execute(function () use ($execution_delayer) {
            $this->assertEquals(1, $execution_delayer->nb_delay);
        });
    }
}
