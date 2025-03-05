<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinksRetriever;
use Tuleap\Tracker\Semantic\TimeframeConfigInvalid;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class SemanticTimeframeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testisTimeframeNotConfiguredNorImpliedReturnsTrueWhenTimeframeNotConfigured(): void
    {
        $semantic_timeframe = new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            new TimeframeNotConfigured()
        );

        self::assertTrue($semantic_timeframe->isTimeframeNotConfiguredNorImplied());
    }

    public function testisTimeframeNotConfiguredNorImpliedReturnsTrueWhenTimeframeImplied(): void
    {
        $semantic_timeframe = $this->createMock(SemanticTimeframe::class);
        $links_retriever    = $this->createMock(LinksRetriever::class);
        $semantic_timeframe = new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            new TimeframeImpliedFromAnotherTracker(
                TrackerTestBuilder::aTracker()->build(),
                $semantic_timeframe,
                $links_retriever
            )
        );

        self::assertTrue($semantic_timeframe->isTimeframeNotConfiguredNorImplied());
    }

    public function testisTimeframeNotConfiguredNorImpliedReturnsFalseWhenTimeframeConfigInvalid(): void
    {
        $semantic_timeframe = new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            new TimeframeConfigInvalid()
        );

        self::assertFalse($semantic_timeframe->isTimeframeNotConfiguredNorImplied());
    }

    public function testisTimeframeNotConfiguredNorImpliedReturnsFalseWhenTimeframeWithDuration(): void
    {
        $start_date_field   = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $duration_field     = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $semantic_timeframe = new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            new TimeframeWithDuration(
                $start_date_field,
                $duration_field
            )
        );

        self::assertFalse($semantic_timeframe->isTimeframeNotConfiguredNorImplied());
    }

    public function testisTimeframeNotConfiguredNorImpliedReturnsFalseWhenTimeframeWithEndDate(): void
    {
        $start_date_field   = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end_date_field     = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $semantic_timeframe = new SemanticTimeframe(
            TrackerTestBuilder::aTracker()->build(),
            new TimeframeWithEndDate(
                $start_date_field,
                $end_date_field
            )
        );

        self::assertFalse($semantic_timeframe->isTimeframeNotConfiguredNorImplied());
    }
}
