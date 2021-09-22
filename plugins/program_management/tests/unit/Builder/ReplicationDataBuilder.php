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

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DomainChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Artifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class ReplicationDataBuilder
{
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
        return self::buildWithIds($artifact_id, $user_id, 578);
    }

    public static function buildWithProjectId(int $project_id): ReplicationData
    {
        return self::buildWithIds(311, 101, $project_id);
    }

    private static function buildWithIds(
        int $artifact_id,
        int $user_id,
        int $project_id
    ): ReplicationData {
        $tracker = ProgramIncrementTrackerIdentifierBuilder::buildWithId(1);
        return new ReplicationData(
            $tracker,
            DomainChangeset::fromId(VerifyIsChangesetStub::withValidChangeset(), 2604),
            new Artifact($artifact_id),
            BuildProjectStub::build(ProjectTestBuilder::aProject()->withId($project_id)->build()),
            UserIdentifierStub::withId($user_id)
        );
    }
}
