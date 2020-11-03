<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Adapter\Program;

use PHPUnit\Framework\TestCase;
use Tracker_Artifact_Changeset;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\ProgramIncrementArtifactLinkType;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactLinkValueAdapterTest extends TestCase
{
    public function testItBuildsArtifactLinkData(): void
    {
        $project         = new \Project(
            ['group_id' => '101', 'unix_group_name' => "project", 'group_name' => 'My project']
        );
        $tracker         = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $source_artifact = new Artifact(101, 1, 102, 123456789, true);
        $source_artifact->setTracker($tracker);
        $adapter   = new ArtifactLinkValueAdapter();
        $user      = UserTestBuilder::aUser()->withId(101)->build();
        $changeset = new Tracker_Artifact_Changeset(
            1,
            $source_artifact,
            $user->getId(),
            12345678,
            "usermail@example.com"
        );

        $replication_data = ReplicationDataAdapter::build($source_artifact, $user, $changeset);

        $artifact_link_data = $adapter->build($replication_data);

        $expected_value = [
            'new_values' => "101",
            'natures'    => ["101" => ProgramIncrementArtifactLinkType::ART_LINK_SHORT_NAME]
        ];
        $this->assertEquals($expected_value, $artifact_link_data->getValues());
    }
}
