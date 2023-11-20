<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueIntegerTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementDateFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementIntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TimeframeChangesetFieldsValueRetrieverTest extends TestCase
{
    private \Tracker $tracker;
    private Artifact $artifact;
    private \Tracker_Artifact_Changeset $changeset;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->tracker   = TrackerTestBuilder::aTracker()->build();
        $this->artifact  = ArtifactTestBuilder::anArtifact(1)
            ->inTracker($this->tracker)
            ->build();
        $this->changeset = ChangesetTestBuilder::aChangeset('1')
            ->ofArtifact($this->artifact)
            ->build();
        $this->user      = UserTestBuilder::anActiveUser()->build();
    }

    public function testItCanGetTimestampFromChangeset(): void
    {
        $date_field = TrackerFormElementDateFieldBuilder::aDateField(1001)->build();
        $date_field->setUserCanRead($this->user, true);
        $this->changeset->setFieldValue(
            $date_field,
            ChangesetValueDateTestBuilder::aValue(101, $this->changeset, $date_field)->withTimestamp(1234567890)->build()
        );

        self::assertEquals(1234567890, TimeframeChangesetFieldsValueRetriever::getTimestamp(
            $date_field,
            $this->user,
            $this->changeset
        ));
    }

    public function testItCannotGetTimestampFromChangesetIfUserCantReadField(): void
    {
        $date_field = TrackerFormElementDateFieldBuilder::aDateField(1001)->build();
        $date_field->setUserCanRead($this->user, false);
        $this->changeset->setFieldValue(
            $date_field,
            ChangesetValueDateTestBuilder::aValue(101, $this->changeset, $date_field)->withTimestamp(1234567890)->build()
        );

        self::expectException(TimeframeFieldNotFoundException::class);

        TimeframeChangesetFieldsValueRetriever::getTimestamp(
            $date_field,
            $this->user,
            $this->changeset
        );
    }

    public function testItCanGetTimestampIfUserIsEncapsulatedInTrackerUserWithReadAllPermission(): void
    {
        $date_field = TrackerFormElementDateFieldBuilder::aDateField(1001)->build();
        $date_field->setUserCanRead($this->user, false);
        $this->changeset->setFieldValue(
            $date_field,
            ChangesetValueDateTestBuilder::aValue(101, $this->changeset, $date_field)->withTimestamp(1324567890)->build()
        );

        self::assertEquals(1324567890, TimeframeChangesetFieldsValueRetriever::getTimestamp(
            $date_field,
            new \Tracker_UserWithReadAllPermission($this->user),
            $this->changeset
        ));
    }

    public function testItCanGetDurationFromChangeset(): void
    {
        $duration_field = TrackerFormElementIntFieldBuilder::anIntField(1002)->build();
        $duration_field->setUserCanRead($this->user, true);
        $this->changeset->setFieldValue(
            $duration_field,
            ChangesetValueIntegerTestBuilder::aValue(101, $this->changeset, $duration_field)->withValue(50)->build()
        );

        self::assertEquals(50, TimeframeChangesetFieldsValueRetriever::getDurationFieldValue(
            $duration_field,
            $this->user,
            $this->changeset
        ));
    }

    public function testItCannotGetDurationFromChangesetIfUserCantReadField(): void
    {
        $duration_field = TrackerFormElementIntFieldBuilder::anIntField(1002)->build();
        $duration_field->setUserCanRead($this->user, false);
        $this->changeset->setFieldValue(
            $duration_field,
            ChangesetValueIntegerTestBuilder::aValue(101, $this->changeset, $duration_field)->withValue(50)->build()
        );

        self::expectException(TimeframeFieldNotFoundException::class);

        TimeframeChangesetFieldsValueRetriever::getDurationFieldValue(
            $duration_field,
            $this->user,
            $this->changeset
        );
    }

    public function testItCanGetDurationIfUserIsEncapsulatedInTrackerUserWithReadAllPermission(): void
    {
        $duration_field = TrackerFormElementIntFieldBuilder::anIntField(1002)->build();
        $duration_field->setUserCanRead($this->user, false);
        $this->changeset->setFieldValue(
            $duration_field,
            ChangesetValueIntegerTestBuilder::aValue(101, $this->changeset, $duration_field)->withValue(52)->build()
        );

        self::assertEquals(52, TimeframeChangesetFieldsValueRetriever::getDurationFieldValue(
            $duration_field,
            new \Tracker_UserWithReadAllPermission($this->user),
            $this->changeset
        ));
    }
}
