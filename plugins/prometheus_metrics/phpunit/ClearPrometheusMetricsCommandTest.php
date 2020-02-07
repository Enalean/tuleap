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

namespace Tuleap\PrometheusMetrics;

use Enalean\Prometheus\Storage\FlushableStorage;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ClearPrometheusMetricsCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCommandFlushesTheStorage() : void
    {
        $storage = \Mockery::mock(FlushableStorage::class);
        $command = new ClearPrometheusMetricsCommand($storage);

        $storage->shouldReceive('flush')->once();

        $command_tester = new CommandTester($command);
        $command_tester->execute([]);

        $this->assertEquals(0, $command_tester->getStatusCode());
    }
}
