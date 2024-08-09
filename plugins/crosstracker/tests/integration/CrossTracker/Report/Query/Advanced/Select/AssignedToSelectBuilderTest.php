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
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\UserListRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\UserRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerReportContentRepresentation;
use Tuleap\CrossTracker\Tests\Report\ArtifactReportFactoryInstantiator;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;
use UserHelper;

final class AssignedToSelectBuilderTest extends CrossTrackerFieldTestCase
{
    private PFUser $user;
    /**
     * @var Tracker[]
     */
    private array $trackers;
    /**
     * @var array<int, UserRepresentation[]>
     */
    private array $expected_results;

    public function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $this->user = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_id);

        $alice = $core_builder->buildUser('alice', 'Alice', 'alice@example.com');
        $bob   = $core_builder->buildUser('bob', 'Bob', 'bob@example.com');
        $core_builder->addUserToProjectMembers((int) $alice->getId(), $project_id);
        $core_builder->addUserToProjectMembers((int) $bob->getId(), $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $this->trackers  = [$release_tracker, $sprint_tracker];

        $release_assignee_field_id = $tracker_builder->buildUserListField($release_tracker->getId(), 'field_assignee', 'sb');
        $sprint_assignee_field_id  = $tracker_builder->buildUserListField($sprint_tracker->getId(), 'field_assignee', 'msb');

        $tracker_builder->buildContributorAssigneeSemantic($release_tracker->getId(), $release_assignee_field_id);
        $tracker_builder->buildContributorAssigneeSemantic($sprint_tracker->getId(), $sprint_assignee_field_id);

        $tracker_builder->setReadPermission(
            $release_assignee_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $sprint_assignee_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_artifact_empty_id      = $tracker_builder->buildArtifact($release_tracker->getId());
        $release_artifact_with_alice_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_with_bob_id    = $tracker_builder->buildArtifact($sprint_tracker->getId());

        $tracker_builder->buildLastChangeset($release_artifact_empty_id);
        $release_artifact_with_alice_changeset = $tracker_builder->buildLastChangeset($release_artifact_with_alice_id);
        $sprint_artifact_with_bob_changeset    = $tracker_builder->buildLastChangeset($sprint_artifact_with_bob_id);

        $helper                 = UserHelper::instance();
        $this->expected_results = [
            $release_artifact_empty_id      => [],
            $release_artifact_with_alice_id => [UserRepresentation::fromPFUser($alice, $helper)],
            $sprint_artifact_with_bob_id    => [UserRepresentation::fromPFUser($bob, $helper)],
        ];
        $tracker_builder->buildListValue(
            $release_artifact_with_alice_changeset,
            $release_assignee_field_id,
            (int) $alice->getId(),
        );
        $tracker_builder->buildListValue(
            $sprint_artifact_with_bob_changeset,
            $sprint_assignee_field_id,
            (int) $bob->getId(),
        );
    }

    private function getQueryResults(CrossTrackerReport $report, PFUser $user): CrossTrackerReportContentRepresentation
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
            new CrossTrackerReport(
                1,
                "SELECT @assigned_to WHERE field_assignee = '' OR field_assignee != ''",
                $this->trackers,
                true,
            ),
            $this->user,
        );

        self::assertSame(3, $result->getTotalSize());
        self::assertCount(2, $result->selected);
        self::assertSame('@assigned_to', $result->selected[1]->name);
        self::assertSame('list_user', $result->selected[1]->type);
        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('@assigned_to', $artifact);
            $value = $artifact['@assigned_to'];
            self::assertInstanceOf(UserListRepresentation::class, $value);
            $values[] = $value->value;
        }
        self::assertEqualsCanonicalizing(array_values($this->expected_results), $values);
    }
}
