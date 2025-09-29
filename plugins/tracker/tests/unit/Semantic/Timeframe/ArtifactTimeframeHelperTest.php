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
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Timeframe\BuildSemanticTimeframeStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactTimeframeHelperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private NullLogger $logger;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger = new NullLogger();
    }

    public function testItShouldReturnFalseIfSemanticIsNotDefined(): void
    {
        $user                       = UserTestBuilder::buildWithDefaults();
        $tracker                    = TrackerTestBuilder::aTracker()->build();
        $duration_field             = IntegerFieldBuilder::anIntField(1002)->inTracker($tracker)->build();
        $start_date_field           = DateFieldBuilder::aDateField(1001)->withReadPermission($user, true)->build();
        $semantic_timeframe_builder = BuildSemanticTimeframeStub::withTimeframeSemanticNotConfigured($tracker);
        $changeset                  = ChangesetTestBuilder::aChangeset(1003)->build();
        $changeset_start_date_value = ChangesetValueDateTestBuilder::aValue(
            1004,
            $changeset,
            $start_date_field
        )->withTimestamp(1234567890)->build();
        $changeset->setFieldValue($start_date_field, $changeset_start_date_value);
        $artifact = ArtifactTestBuilder::anArtifact(5555)->withChangesets($changeset)->build();

        $artifact_timeframe_helper = new ArtifactTimeframeHelper($semantic_timeframe_builder, $this->logger);

        $this->assertFalse($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $duration_field, $artifact, $changeset));
    }

    public function testItShouldReturnFalseIfNotUsedInSemantics(): void
    {
        $user                       = UserTestBuilder::buildWithDefaults();
        $tracker                    = TrackerTestBuilder::aTracker()->build();
        $duration_field             = IntegerFieldBuilder::anIntField(1002)->inTracker($tracker)->build();
        $start_date_field           = DateFieldBuilder::aDateField(1001)->withReadPermission($user, true)->build();
        $end_date_field             = DateFieldBuilder::aDateField(1003)->inTracker($tracker)->build();
        $semantic_timeframe_builder = BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnDuration($tracker, $start_date_field, $duration_field);
        $changeset                  = ChangesetTestBuilder::aChangeset(1004)->build();
        $changeset_start_date_value = ChangesetValueDateTestBuilder::aValue(
            1004,
            $changeset,
            $start_date_field
        )->withTimestamp(1234567890)->build();
        $changeset->setFieldValue($start_date_field, $changeset_start_date_value);
        $artifact = ArtifactTestBuilder::anArtifact(5555)->withChangesets($changeset)->build();

        $artifact_timeframe_helper = new ArtifactTimeframeHelper($semantic_timeframe_builder, $this->logger);

        $this->assertFalse($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $end_date_field, $artifact, $changeset));
    }

    public function testItShouldReturnFalseIfUserCannotViewStartDate(): void
    {
        $user                       = UserTestBuilder::buildWithDefaults();
        $tracker                    = TrackerTestBuilder::aTracker()->build();
        $duration_field             = IntegerFieldBuilder::anIntField(1002)->inTracker($tracker)->build();
        $start_date_field           = DateFieldBuilder::aDateField(1001)->withReadPermission($user, false)->build();
        $semantic_timeframe_builder = BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnDuration($tracker, $start_date_field, $duration_field);
        $changeset                  = ChangesetTestBuilder::aChangeset(1003)->build();
        $changeset_start_date_value = ChangesetValueDateTestBuilder::aValue(
            1004,
            $changeset,
            $start_date_field
        )->withTimestamp(1234567890)->build();
        $changeset->setFieldValue($start_date_field, $changeset_start_date_value);
        $artifact = ArtifactTestBuilder::anArtifact(5555)->withChangesets($changeset)->build();

        $artifact_timeframe_helper = new ArtifactTimeframeHelper($semantic_timeframe_builder, $this->logger);

        $this->assertFalse($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $duration_field, $artifact, $changeset));
    }

    public function testItShouldReturnTrueIfUserShouldBeShownArtifactHelperForDuration(): void
    {
        $user                       = UserTestBuilder::buildWithDefaults();
        $tracker                    = TrackerTestBuilder::aTracker()->build();
        $duration_field             = IntegerFieldBuilder::anIntField(1002)->inTracker($tracker)->build();
        $start_date_field           = DateFieldBuilder::aDateField(1001)->withReadPermission($user, true)->build();
        $semantic_timeframe_builder = BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnDuration($tracker, $start_date_field, $duration_field);
        $changeset                  = ChangesetTestBuilder::aChangeset(1003)->build();
        $changeset_start_date_value = ChangesetValueDateTestBuilder::aValue(
            1004,
            $changeset,
            $start_date_field
        )->withTimestamp(1234567890)->build();
        $changeset->setFieldValue($start_date_field, $changeset_start_date_value);
        $artifact = ArtifactTestBuilder::anArtifact(5555)->withChangesets($changeset)->build();

        $artifact_timeframe_helper = new ArtifactTimeframeHelper($semantic_timeframe_builder, $this->logger);

        $this->assertTrue($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $duration_field, $artifact, $changeset));
    }

    public function testItShouldReturnTrueIfUserShouldBeShownArtifactHelperForEndDate(): void
    {
        $user                       = UserTestBuilder::buildWithDefaults();
        $tracker                    = TrackerTestBuilder::aTracker()->build();
        $end_date_field             = DateFieldBuilder::aDateField(1002)->inTracker($tracker)->build();
        $start_date_field           = DateFieldBuilder::aDateField(1001)->withReadPermission($user, true)->build();
        $semantic_timeframe_builder = BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnEndDate($tracker, $start_date_field, $end_date_field);
        $changeset                  = ChangesetTestBuilder::aChangeset(1003)->build();
        $changeset_start_date_value = ChangesetValueDateTestBuilder::aValue(
            1004,
            $changeset,
            $start_date_field
        )->withTimestamp(1234567890)->build();
        $changeset->setFieldValue($start_date_field, $changeset_start_date_value);
        $artifact = ArtifactTestBuilder::anArtifact(5555)->withChangesets($changeset)->build();

        $artifact_timeframe_helper = new ArtifactTimeframeHelper($semantic_timeframe_builder, $this->logger);

        $this->assertTrue($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $end_date_field, $artifact, $changeset));
    }

    public function testItShouldNotDisplayTheHelperOnStartDateField(): void
    {
        $user                       = UserTestBuilder::buildWithDefaults();
        $tracker                    = TrackerTestBuilder::aTracker()->build();
        $start_date_field           = DateFieldBuilder::aDateField(1001)->withReadPermission($user, true)->inTracker($tracker)->build();
        $end_date_field             = DateFieldBuilder::aDateField(1002)->inTracker($tracker)->build();
        $semantic_timeframe_builder = BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnEndDate($tracker, $start_date_field, $end_date_field);
        $changeset                  = ChangesetTestBuilder::aChangeset(1003)->build();
        $changeset_start_date_value = ChangesetValueDateTestBuilder::aValue(
            1004,
            $changeset,
            $start_date_field
        )->withTimestamp(1234567890)->build();
        $changeset->setFieldValue($start_date_field, $changeset_start_date_value);
        $artifact = ArtifactTestBuilder::anArtifact(5555)->withChangesets($changeset)->build();

        $artifact_timeframe_helper = new ArtifactTimeframeHelper($semantic_timeframe_builder, $this->logger);

        $this->assertFalse($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $start_date_field, $artifact, $changeset));
    }

    public function testItShouldNotDisplayTheHelperWithStartDateFieldValueEmpty(): void
    {
        $user                       = UserTestBuilder::buildWithDefaults();
        $tracker                    = TrackerTestBuilder::aTracker()->build();
        $start_date_field           = DateFieldBuilder::aDateField(1001)->withReadPermission($user, true)->build();
        $end_date_field             = DateFieldBuilder::aDateField(1002)->inTracker($tracker)->build();
        $semantic_timeframe_builder = BuildSemanticTimeframeStub::withTimeframeSemanticBasedOnEndDate($tracker, $start_date_field, $end_date_field);
        $changeset                  = ChangesetTestBuilder::aChangeset(1003)->build();
        $changeset_start_date_value = ChangesetValueDateTestBuilder::aValue(
            1004,
            $changeset,
            $start_date_field
        )->withTimestamp(0)->build();
        $changeset->setFieldValue($start_date_field, $changeset_start_date_value);
        $artifact = ArtifactTestBuilder::anArtifact(5555)->withChangesets($changeset)->build();

        $artifact_timeframe_helper = new ArtifactTimeframeHelper($semantic_timeframe_builder, $this->logger);

        $this->assertFalse($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $end_date_field, $artifact, $changeset));
    }
}
