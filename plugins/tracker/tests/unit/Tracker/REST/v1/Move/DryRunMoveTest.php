<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Move;

use PFUser;
use Tracker;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Action\DuckTypedMoveFieldCollection;
use Tuleap\Tracker\Action\FieldMapping;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\MinimalFieldRepresentation;
use Tuleap\Tracker\REST\v1\MoveArtifactCompleteFeatureFlag;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\CheckMoveArtifactStub;
use Tuleap\Tracker\Test\Stub\CollectDryRunTypingFieldStub;
use Tuleap\Tracker\Test\Stub\FeedbackFieldCollectorInterfaceStub;

final class DryRunMoveTest extends TestCase
{
    use ForgeConfigSandbox;

    private Tracker $source_tracker;
    private Tracker $target_tracker;
    private Artifact $artifact;
    private PFUser $user;

    protected function setUp(): void
    {
        $this->source_tracker = TrackerTestBuilder::aTracker()->withName("Source tracker")->build();
        $this->target_tracker = TrackerTestBuilder::aTracker()->withName("Target tracker")->build();
        $this->artifact       = ArtifactTestBuilder::anArtifact(12)->build();
        $this->user           = UserTestBuilder::anActiveUser()->build();
    }

    public function testItDoesNotPerformTheDuckTypedBasedDryRunWhenFeatureFlagIsDisabled(): void
    {
        $migrated_fields           = [TrackerFormElementStringFieldBuilder::aStringField(102)->withName("title")->build()];
        $not_migrated_fields       = [TrackerFormElementStringFieldBuilder::aStringField(103)->withName("summary")->build()];
        $partially_migrated_fields = [];

        $dry_run = new DryRunMover(
            CheckMoveArtifactStub::withPossibleArtifactMove(),
            FeedbackFieldCollectorInterfaceStub::withFields(
                $not_migrated_fields,
                $migrated_fields,
                $partially_migrated_fields
            ),
            CollectDryRunTypingFieldStub::withNoExpectedCalls(),
        );

        $fields_collection = $dry_run->move($this->source_tracker, $this->target_tracker, $this->artifact, $this->user);

        self::assertCount(1, $fields_collection->dry_run->fields->fields_migrated);
        self::assertEquals(new MinimalFieldRepresentation($migrated_fields[0]), $fields_collection->dry_run->fields->fields_migrated[0]);

        self::assertCount(1, $fields_collection->dry_run->fields->fields_not_migrated);
        self::assertEquals(new MinimalFieldRepresentation($not_migrated_fields[0]), $fields_collection->dry_run->fields->fields_not_migrated[0]);

        self::assertCount(0, $fields_collection->dry_run->fields->fields_partially_migrated);
    }

    public function testItPerformsTheDuckTypedBasedDryRunWhenFeatureFlagIsDisabled(): void
    {
        \ForgeConfig::set("feature_flag_" . MoveArtifactCompleteFeatureFlag::FEATURE_FLAG_KEY, "1");

        $source_title_field = TrackerFormElementStringFieldBuilder::aStringField(102)->withName("title")->build();
        $target_title_field = TrackerFormElementStringFieldBuilder::aStringField(202)->withName("title")->build();

        $migrated_fields     = [$source_title_field];
        $not_migrated_fields = [TrackerFormElementStringFieldBuilder::aStringField(103)->withName("summary")->build()];
        $mapping_fields      = [
            FieldMapping::fromFields(
                $source_title_field,
                $target_title_field
            ),
        ];

        $dry_run = new DryRunMover(
            CheckMoveArtifactStub::withPossibleArtifactMove(),
            FeedbackFieldCollectorInterfaceStub::withNoExpectedCalls(),
            CollectDryRunTypingFieldStub::withCollectionOfField(
                DuckTypedMoveFieldCollection::fromFields(
                    $migrated_fields,
                    $not_migrated_fields,
                    $mapping_fields
                )
            ),
        );

        $fields_collection = $dry_run->move($this->source_tracker, $this->target_tracker, $this->artifact, $this->user);

        self::assertCount(1, $fields_collection->dry_run->fields->fields_migrated);
        self::assertEquals(new MinimalFieldRepresentation($migrated_fields[0]), $fields_collection->dry_run->fields->fields_migrated[0]);

        self::assertCount(1, $fields_collection->dry_run->fields->fields_not_migrated);
        self::assertEquals(new MinimalFieldRepresentation($not_migrated_fields[0]), $fields_collection->dry_run->fields->fields_not_migrated[0]);

        self::assertCount(0, $fields_collection->dry_run->fields->fields_partially_migrated);
    }
}
