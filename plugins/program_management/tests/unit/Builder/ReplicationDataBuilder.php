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
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ReplicationDataBuilder
{
    private const SUBMISSION_TIMESTAMP = 1234567890;

    public static function build(): ReplicationData
    {
        return self::buildWithArtifactId(311);
    }

    public static function buildWithArtifactId(int $artifact_id): ReplicationData
    {
        return self::buildWithArtifactIdAndUserId($artifact_id, 101);
    }

    public static function buildWithArtifactIdAndUserId(int $artifact_id, int $user_id): ReplicationData
    {
        $source_program_increment = self::buildArtifact($artifact_id, 578);
        return self::buildFromArtifact($source_program_increment, $user_id);
    }

    public static function buildWithProjectId(int $project_id): ReplicationData
    {
        $source_program_increment = self::buildArtifact(311, $project_id);
        return self::buildFromArtifact($source_program_increment, 101);
    }

    private static function buildFromArtifact(
        Artifact $source_program_increment,
        int $user_id
    ): ReplicationData {
        $user             = UserTestBuilder::aUser()->withId($user_id)->build();
        $source_changeset = new \Tracker_Artifact_Changeset(
            2604,
            $source_program_increment,
            $user->getId(),
            self::SUBMISSION_TIMESTAMP,
            null
        );

        $tracker = ProgramIncrementTrackerIdentifierBuilder::buildWithId(1);
        return ReplicationDataAdapter::build($source_program_increment, $user, $source_changeset, $tracker);
    }

    private static function buildArtifact(int $artifact_id, int $project_id): Artifact
    {
        $program_project        = ProjectTestBuilder::aProject()->withId($project_id)->build();
        $source_timebox_tracker = TrackerTestBuilder::aTracker()
            ->withId(1)
            ->withProject($program_project)
            ->build();
        return ArtifactTestBuilder::anArtifact($artifact_id)
            ->withSubmissionTimestamp(self::SUBMISSION_TIMESTAMP)
            ->inTracker($source_timebox_tracker)
            ->inProject($program_project)
            ->build();
    }
}
