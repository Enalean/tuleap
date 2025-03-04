<?php
/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker;

use Tracker_UgroupMappingBuilder;
use Tuleap\Project\Duplication\DuplicationType;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerDuplicationUserGroupMappingTest extends TestCase
{
    private Tracker_UgroupMappingBuilder&\PHPUnit\Framework\MockObject\MockObject $builder;

    protected function setUp(): void
    {
        $this->builder = $this->createMock(Tracker_UgroupMappingBuilder::class);
    }

    public function testItDuplicatesFromNewProjectWhenMappingIsProvided(): void
    {
        $ugroup_mapping   =  [123 => 456];
        $project          = ProjectTestBuilder::aProject()->withId(101)->build();
        $template_tracker = TrackerTestBuilder::aTracker()->build();

        $result = TrackerDuplicationUserGroupMapping::fromMapping($this->builder, $ugroup_mapping, $template_tracker, $project);
        self::assertEquals($ugroup_mapping, $result->ugroup_mapping);
        self::assertEquals(DuplicationType::DUPLICATE_NEW_PROJECT, $result->duplication_type);
    }

    public function testItDuplicatesInsideSameProject(): void
    {
        $ugroup_mapping   = [];
        $project          = ProjectTestBuilder::aProject()->withId(101)->build();
        $template_tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();

        $result = TrackerDuplicationUserGroupMapping::fromMapping($this->builder, $ugroup_mapping, $template_tracker, $project);
        self::assertEmpty($ugroup_mapping);
        self::assertSame(DuplicationType::DUPLICATE_SAME_PROJECT, $result->duplication_type);
    }

    public function testItBuildFromOtherProject(): void
    {
        $initial_ugroup_mapping       = [];
        $other_project_ugroup_mapping =  [102 => 456];
        $project                      = ProjectTestBuilder::aProject()->withId(101)->build();
        $other_project                = ProjectTestBuilder::aProject()->withId(102)->build();
        $template_tracker             = TrackerTestBuilder::aTracker()->withProject($other_project)->build();
        $this->builder->method('getMapping')->willReturn($other_project_ugroup_mapping);

        $result = TrackerDuplicationUserGroupMapping::fromMapping($this->builder, $initial_ugroup_mapping, $template_tracker, $project);
        self::assertEqualsCanonicalizing($other_project_ugroup_mapping, $result->ugroup_mapping);
        self::assertSame(DuplicationType::DUPLICATE_OTHER_PROJECT, $result->duplication_type);
    }
}
