<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\Tracker\NewDropdown\TrackerInNewDropdownDao;

final class TrackerCreationSettingsBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildTrackerCreationSettings(): void
    {
        $in_new_dropdown_dao = \Mockery::mock(TrackerInNewDropdownDao::class);
        $builder             = new TrackerCreationSettingsBuilder($in_new_dropdown_dao);
        $tracker             = \Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(10);

        $expected = new TrackerCreationSettings(true);

        $in_new_dropdown_dao->shouldReceive('isContaining')->with($tracker->getId())->andReturnTrue();
        $result = $builder->build($tracker);

        $this->assertEquals($expected->isDisplayedInNewDropdown(), $result->isDisplayedInNewDropdown());
    }
}
