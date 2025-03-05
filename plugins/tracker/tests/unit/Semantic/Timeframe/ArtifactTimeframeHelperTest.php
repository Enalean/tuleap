<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Semantic\Timeframe;

use Psr\Log\NullLogger;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactTimeframeHelperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private NullLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new NullLogger();
    }

    public function testItShouldReturnFalseIfSemanticIsNotDefined(): void
    {
        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $user                       = UserTestBuilder::buildWithDefaults();
        $tracker                    = TrackerTestBuilder::aTracker()->build();
        $duration_field             = IntFieldBuilder::anIntField(1002)->inTracker($tracker)->build();
        $semantic                   = $this->createMock(SemanticTimeframe::class);

        $semantic_timeframe_builder->method('getSemantic')->with($tracker)->willReturn($semantic);
        $semantic->method('isDefined')->willReturn(false);

        $artifact_timeframe_helper = new ArtifactTimeframeHelper($semantic_timeframe_builder, $this->logger);

        $this->assertFalse($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $duration_field));
    }

    public function testItShouldReturnFalseIfNotUsedInSemantics(): void
    {
        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $user                       = UserTestBuilder::buildWithDefaults();
        $tracker                    = TrackerTestBuilder::aTracker()->build();
        $duration_field             = IntFieldBuilder::anIntField(1002)->inTracker($tracker)->build();
        $semantic                   = $this->createMock(SemanticTimeframe::class);
        $start_date_field           = DateFieldBuilder::aDateField(1001)->withReadPermission($user, true)->build();

        $semantic_timeframe_builder->method('getSemantic')->with($tracker)->willReturn($semantic);
        $semantic->method('isDefined')->willReturn(true);
        $semantic->method('isUsedInSemantics')->with($duration_field)->willReturn(false);
        $semantic->method('getStartDateField')->willReturn($start_date_field);

        $artifact_timeframe_helper = new ArtifactTimeframeHelper($semantic_timeframe_builder, $this->logger);

        $this->assertFalse($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $duration_field));
    }

    public function testItShouldReturnFalseIfUserCannotViewStartDate(): void
    {
        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $user                       = UserTestBuilder::buildWithDefaults();
        $tracker                    = TrackerTestBuilder::aTracker()->build();
        $duration_field             = IntFieldBuilder::anIntField(1002)->inTracker($tracker)->build();
        $semantic                   = $this->createMock(SemanticTimeframe::class);
        $start_date_field           = DateFieldBuilder::aDateField(1001)->withReadPermission($user, false)->build();

        $semantic_timeframe_builder->method('getSemantic')->with($tracker)->willReturn($semantic);
        $semantic->method('isDefined')->willReturn(true);
        $semantic->method('isUsedInSemantics')->with($duration_field)->willReturn(true);
        $semantic->method('getStartDateField')->willReturn($start_date_field);

        $artifact_timeframe_helper = new ArtifactTimeframeHelper($semantic_timeframe_builder, $this->logger);

        $this->assertFalse($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $duration_field));
    }

    public function testItShouldReturnTrueIfUserShouldBeShownArtifactHelperForDuration(): void
    {
        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $user                       = UserTestBuilder::buildWithDefaults();
        $tracker                    = TrackerTestBuilder::aTracker()->build();
        $duration_field             = IntFieldBuilder::anIntField(1002)->inTracker($tracker)->build();
        $semantic                   = $this->createMock(SemanticTimeframe::class);
        $start_date_field           = DateFieldBuilder::aDateField(1001)->withReadPermission($user, true)->build();

        $semantic_timeframe_builder->method('getSemantic')->with($tracker)->willReturn($semantic);
        $semantic->method('isDefined')->willReturn(true);
        $semantic->method('isUsedInSemantics')->with($duration_field)->willReturn(true);
        $semantic->method('getStartDateField')->willReturn($start_date_field);

        $artifact_timeframe_helper = new ArtifactTimeframeHelper($semantic_timeframe_builder, $this->logger);

        $this->assertTrue($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $duration_field));
    }

    public function testItShouldReturnTrueIfUserShouldBeShownArtifactHelperForEndDate(): void
    {
        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $user                       = UserTestBuilder::buildWithDefaults();
        $tracker                    = TrackerTestBuilder::aTracker()->build();
        $end_date_field             = IntFieldBuilder::anIntField(1002)->inTracker($tracker)->build();
        $semantic                   = $this->createMock(SemanticTimeframe::class);
        $start_date_field           = DateFieldBuilder::aDateField(1001)->withReadPermission($user, true)->build();

        $semantic_timeframe_builder->method('getSemantic')->with($tracker)->willReturn($semantic);
        $semantic->method('isDefined')->willReturn(true);
        $semantic->method('isUsedInSemantics')->with($end_date_field)->willReturn(true);
        $semantic->method('getStartDateField')->willReturn($start_date_field);

        $artifact_timeframe_helper = new ArtifactTimeframeHelper($semantic_timeframe_builder, $this->logger);

        $this->assertTrue($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $end_date_field));
    }

    public function testItShouldNotDisplayTheHelperOnStartDateField(): void
    {
        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $user                       = UserTestBuilder::buildWithDefaults();
        $tracker                    = TrackerTestBuilder::aTracker()->build();
        $semantic                   = $this->createMock(SemanticTimeframe::class);
        $start_date_field           = DateFieldBuilder::aDateField(1001)->withReadPermission($user, true)->inTracker($tracker)->build();

        $semantic_timeframe_builder->method('getSemantic')->with($tracker)->willReturn($semantic);
        $semantic->method('isDefined')->willReturn(true);
        $semantic->method('getStartDateField')->willReturn($start_date_field);

        $artifact_timeframe_helper = new ArtifactTimeframeHelper($semantic_timeframe_builder, $this->logger);

        $this->assertFalse($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $start_date_field));
    }
}
