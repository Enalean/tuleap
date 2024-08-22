<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\Select;

use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerDefaultReport;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\ProjectRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerReportContentRepresentation;
use Tuleap\CrossTracker\Tests\Report\ArtifactReportFactoryInstantiator;
use Tuleap\DB\DBFactory;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class ProjectNameSelectBuilderTest extends CrossTrackerFieldTestCase
{
    private PFUser $user;
    /**
     * @var Tracker[]
     */
    private array $trackers;
    /**
     * @var array<int, ProjectRepresentation>
     */
    private array $expected_values;

    public function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project_1    = $core_builder->buildProject('My project');
        $project_1_id = (int) $project_1->getId();
        $project_2    = $core_builder->buildProject('Another project', EmojiCodepointConverter::convertEmojiToStoreFormat('⚔️'));
        $project_2_id = (int) $project_2->getId();
        $this->user   = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_1_id);
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_2_id);

        $release_tracker = $tracker_builder->buildTracker($project_1_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_2_id, 'Sprint');
        $this->trackers  = [$release_tracker, $sprint_tracker];

        $release_artifact_id_field_id = $tracker_builder->buildArtifactIdField($release_tracker->getId());
        $sprint_artifact_id_field_id  = $tracker_builder->buildArtifactIdField($sprint_tracker->getId());

        $tracker_builder->setReadPermission(
            $release_artifact_id_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $sprint_artifact_id_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_artifact_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_id  = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $tracker_builder->buildLastChangeset($release_artifact_id);
        $tracker_builder->buildLastChangeset($sprint_artifact_id);

        $this->expected_values = [
            $release_artifact_id => new ProjectRepresentation('My project', ''),
            $sprint_artifact_id  => new ProjectRepresentation('Another project', '⚔️'),
        ];
    }

    private function getQueryResults(CrossTrackerDefaultReport $report, PFUser $user): CrossTrackerReportContentRepresentation
    {
        $result = (new ArtifactReportFactoryInstantiator())
            ->getFactory()
            ->getArtifactsMatchingReport($report, $user, 10, 0);
        assert($result instanceof CrossTrackerReportContentRepresentation);
        return $result;
    }

    public function testItReturnsColumns(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerDefaultReport(
                1,
                'SELECT @project.name FROM @project = "self" WHERE @id >= 1',
                $this->trackers,
                true,
            ),
            $this->user,
        );

        self::assertSame(2, $result->getTotalSize());
        self::assertCount(2, $result->selected);
        self::assertSame('@project.name', $result->selected[1]->name);
        self::assertSame('project', $result->selected[1]->type);
        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('@project.name', $artifact);
            $value = $artifact['@project.name'];
            self::assertInstanceOf(ProjectRepresentation::class, $value);
            $values[] = $value;
        }
        self::assertEqualsCanonicalizing(array_values($this->expected_values), $values);
    }
}
