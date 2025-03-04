<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace TuleapCfg\Command\SiteDeploy\Nginx;

use PHPUnit\Framework\Attributes\DataProvider;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NginxServerNamesHashBucketSizeCalculatorTest extends TestCase
{
    #[DataProvider('dataProviderServerNamesExpectedHashBucketSize')]
    public function testHashBucketSizeCalculation(int $server_name_size, int $expected_hash_bucket_size): void
    {
        $cpu_information        = new FakeX8664CPUInformationStub();
        $bucket_size_calculator = new NginxServerNamesHashBucketSizeCalculator($cpu_information);

        self::assertSame($expected_hash_bucket_size, $bucket_size_calculator->computeServerNamesHashBucketSize(str_repeat('a', $server_name_size)));
    }

    public static function dataProviderServerNamesExpectedHashBucketSize(): array
    {
        return [
            [11, 64],
            [32, 64],
            [56, 128],
            [64, 128],
            [80, 128],
            [238, 256],
            [250, 512],
        ];
    }
}
