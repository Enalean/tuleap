<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use PlanningParameters;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanningParametersTest extends TestCase
{
    public function testPlanningParametersIsAnEmptyArrayWhenKeyDoesNotExists(): void
    {
        $array      = [];
        $parameters = PlanningParameters::fromArray($array);
        self::assertSame([], $parameters->backlog_tracker_ids);
    }

    public function testPlanningParametersValueCanBePassedAsNull(): void
    {
        $array      = [
            PlanningParameters::BACKLOG_TRACKER_IDS => [null, 13],
        ];
        $parameters = PlanningParameters::fromArray($array);
        self::assertSame([13], $parameters->backlog_tracker_ids);
    }

    public function testItBuildsPlanningParameters(): void
    {
        $array      = [
            PlanningParameters::BACKLOG_TRACKER_IDS => ['12', 13],
        ];
        $parameters = PlanningParameters::fromArray($array);
        self::assertSame([12, 13], $parameters->backlog_tracker_ids);
    }
}
