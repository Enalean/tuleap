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

namespace Tuleap\ProgramManagement\Domain\Program;

use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ReplicationDataAdapter;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIdentifierTest extends TestCase
{
    public function testItBuildsAProgramIdentifierFromAProjectId(): void
    {
        $builder    = BuildProgramStub::stubValidProgram();
        $project_id = 101;
        $user       = UserTestBuilder::aUser()->build();

        $program = ProgramIdentifier::fromId($builder, $project_id, $user);
        self::assertSame($project_id, $program->getId());
    }

    public function testItBuildsAProgramIdentifierFromReplicationData(): void
    {
        $project          = ProjectTestBuilder::aProject()->withId(102)->build();
        $tracker          = TrackerTestBuilder::aTracker()->withId(76)->withProject($project)->build();
        $artifact         = ArtifactTestBuilder::anArtifact(7)->inTracker($tracker)->build();
        $changeset        = new \Tracker_Artifact_Changeset(90, $artifact, 110, 1234567890, null);
        $user             = UserTestBuilder::aUser()->build();
        $replication_data = ReplicationDataAdapter::build($artifact, $user, $changeset);

        $program = ProgramIdentifier::fromReplicationData($replication_data);
        self::assertSame(102, $program->getId());
    }
}
