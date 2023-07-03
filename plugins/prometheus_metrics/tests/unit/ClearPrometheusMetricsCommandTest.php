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
use Symfony\Component\Console\Tester\CommandTester;

final class ClearPrometheusMetricsCommandTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testCommandFlushesTheStorage(): void
    {
        $storage = $this->createMock(FlushableStorage::class);
        $command = new ClearPrometheusMetricsCommand($storage);

        $storage->expects(self::once())->method('flush');

        $command_tester = new CommandTester($command);
        $command_tester->execute([]);

        self::assertEquals(0, $command_tester->getStatusCode());
    }
}
