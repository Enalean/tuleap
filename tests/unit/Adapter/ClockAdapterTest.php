<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Baseline\Adapter;

require_once __DIR__ . '/../bootstrap.php';

use DateTimeImmutable;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Baseline\Domain\Clock;

class ClockAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Clock */
    private $clock;

    /**
     * @before
     */
    public function createInstance()
    {
        $this->clock = new ClockAdapter();
    }

    public function testNowReturnsCurrentDateTime()
    {
        $before_now = new DateTimeImmutable("@$_SERVER[REQUEST_TIME]");
        $now        = $this->clock->now();
        $after_now  = new DateTimeImmutable("@$_SERVER[REQUEST_TIME]");

        $this->assertGreaterThanOrEqual($before_now, $now);
        $this->assertLessThanOrEqual($after_now, $now);
    }
}
