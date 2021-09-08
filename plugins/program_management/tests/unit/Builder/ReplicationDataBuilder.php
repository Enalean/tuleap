<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ReplicationDataAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ReplicationDataBuilder
{
    public static function build(): ReplicationData
    {
        return self::buildWithArtifactId(311);
    }

    public static function buildWithArtifactId(int $artifact_id): ReplicationData
    {
        return self::buildWithArtifactIdAndSubmissionDate($artifact_id, 1234567890);
    }

    public static function buildWithArtifactIdAndUserId(int $artifact_id, int $user_id): ReplicationData
    {
        $source_program_increment = self::buildArtifact($artifact_id, 1234567890, 578);
        return self::buildFromArtifact($source_program_increment, 1234567890, $user_id);
    }

    public static function buildWithProjectId(int $project_id): ReplicationData
    {
        $source_program_increment = self::buildArtifact(311, 1234567890, $project_id);
        return self::buildFromArtifact($source_program_increment, 1234567890, 101);
    }

    public static function buildWithArtifactIdAndSubmissionDate(
        int $artifact_id,
        int $submission_timestamp
    ): ReplicationData {
        $source_program_increment = self::buildArtifact($artifact_id, $submission_timestamp, 578);
        return self::buildFromArtifact($source_program_increment, $submission_timestamp, 101);
    }

    private static function buildFromArtifact(
        Artifact $source_program_increment,
        int $submission_timestamp,
        int $user_id
    ): ReplicationData {
        $user             = UserTestBuilder::aUser()->withId($user_id)->build();
        $source_changeset = new \Tracker_Artifact_Changeset(
            2604,
            $source_program_increment,
            $user->getId(),
            $submission_timestamp,
            null
        );

        $tracker = ProgramIncrementTrackerIdentifier::fromId(
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement(),
            1
        );
        return ReplicationDataAdapter::build($source_program_increment, $user, $source_changeset, $tracker);
    }

    private static function buildArtifact(int $artifact_id, int $submission_timestamp, int $project_id): Artifact
    {
        $program_project        = ProjectTestBuilder::aProject()->withId($project_id)->build();
        $source_timebox_tracker = TrackerTestBuilder::aTracker()
            ->withId(1)
            ->withProject($program_project)
            ->build();
        return ArtifactTestBuilder::anArtifact($artifact_id)
            ->withSubmissionTimestamp($submission_timestamp)
            ->inTracker($source_timebox_tracker)
            ->inProject($program_project)
            ->build();
    }
}
