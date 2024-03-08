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

namespace Tuleap\CrossTracker\Report\Query\Advanced\Metadata;

use PFUser;
use ProjectUGroup;
use Tracker;
use Tracker_FormElement_Field_List;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Tests\Report\ArtifactReportFactoryInstantiator;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;
use UserManager;

final class AssignedToMetadataTest extends CrossTrackerFieldTestCase
{
    private PFUser $project_member;
    private PFUser $alice;
    private Tracker $release_tracker;
    private Tracker $sprint_tracker;
    private int $release_artifact_empty_id;
    private int $release_artifact_with_alice_id;
    private int $sprint_artifact_empty_id;
    private int $sprint_artifact_with_alice_bob_id;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project              = $core_builder->buildProject();
        $project_id           = (int) $project->getID();
        $this->project_member = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->project_member->getId(), $project_id);

        $this->alice = $core_builder->buildUser('alice', 'Alice', 'alice@example.com');
        $bob         = $core_builder->buildUser('bob', 'Bob', 'bob@example.com');
        $core_builder->addUserToProjectMembers((int) $this->alice->getId(), $project_id);
        $core_builder->addUserToProjectMembers((int) $bob->getId(), $project_id);

        $user_manager = $this->createPartialMock(UserManager::class, ['getCurrentUser']);
        $user_manager->method('getCurrentUser')->willReturn($this->alice);
        UserManager::setInstance($user_manager);

        $this->release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $this->sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');

        $release_assignee_field_id = $tracker_builder->buildUserListField($this->release_tracker->getId(), 'release_assignee', 'sb');
        $sprint_assignee_field_id  = $tracker_builder->buildUserListField($this->sprint_tracker->getId(), 'sprint_assignee', 'msb');

        $tracker_builder->buildContributorAssigneeSemantic($this->release_tracker->getId(), $release_assignee_field_id);
        $tracker_builder->buildContributorAssigneeSemantic($this->sprint_tracker->getId(), $sprint_assignee_field_id);

        $tracker_builder->setReadPermission(
            $release_assignee_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $sprint_assignee_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $this->release_artifact_empty_id         = $tracker_builder->buildArtifact($this->release_tracker->getId());
        $this->release_artifact_with_alice_id    = $tracker_builder->buildArtifact($this->release_tracker->getId());
        $this->sprint_artifact_empty_id          = $tracker_builder->buildArtifact($this->sprint_tracker->getId());
        $this->sprint_artifact_with_alice_bob_id = $tracker_builder->buildArtifact($this->sprint_tracker->getId());

        $release_artifact_empty_changeset         = $tracker_builder->buildLastChangeset($this->release_artifact_empty_id);
        $release_artifact_with_alice_changeset    = $tracker_builder->buildLastChangeset($this->release_artifact_with_alice_id);
        $sprint_artifact_empty_changeset          = $tracker_builder->buildLastChangeset($this->sprint_artifact_empty_id);
        $sprint_artifact_with_alice_bob_changeset = $tracker_builder->buildLastChangeset($this->sprint_artifact_with_alice_bob_id);

        $tracker_builder->buildListValue(
            $release_artifact_empty_changeset,
            $release_assignee_field_id,
            Tracker_FormElement_Field_List::NONE_VALUE,
        );
        $tracker_builder->buildListValue(
            $release_artifact_with_alice_changeset,
            $release_assignee_field_id,
            (int) $this->alice->getId(),
        );
        $tracker_builder->buildListValue(
            $sprint_artifact_empty_changeset,
            $sprint_assignee_field_id,
            Tracker_FormElement_Field_List::NONE_VALUE,
        );
        $tracker_builder->buildListValue(
            $sprint_artifact_with_alice_bob_changeset,
            $sprint_assignee_field_id,
            (int) $this->alice->getId(),
        );
        $tracker_builder->buildListValue(
            $sprint_artifact_with_alice_bob_changeset,
            $sprint_assignee_field_id,
            (int) $bob->getId(),
        );
    }

    /**
     * @return list<int>
     * @throws SearchablesDoNotExistException
     * @throws SearchablesAreInvalidException
     */
    private function getMatchingArtifactIds(CrossTrackerReport $report, PFUser $user): array
    {
        $artifacts = (new ArtifactReportFactoryInstantiator())
            ->getFactory()
            ->getArtifactsMatchingReport($report, $user, 10, 0)
            ->getArtifacts();
        return array_values(array_map(static fn(Artifact $artifact) => $artifact->getId(), $artifacts));
    }

    public function testEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to = ''",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testEqualUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to = 'alice'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testEqualMyself(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to = MYSELF()",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->alice
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to = 'alice' AND @assigned_to = 'bob'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testNotEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to != ''",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testNotEqualUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to != 'bob'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->release_artifact_with_alice_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testNotEqualMyself(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to != MYSELF()",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->alice
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to != 'alice' AND @assigned_to != 'bob'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testInUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to IN('alice')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testInMyself(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to IN(MYSELF())",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->alice
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testInMyselfUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to IN(MYSELF(), 'bob')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->alice
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testInMultipleUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to IN('alice', 'bob')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testMultipleIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to IN('alice') OR @assigned_to IN('bob')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testNotInUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to NOT IN('bob')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->release_artifact_with_alice_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testNotInMyself(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to NOT IN(MYSELF())",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->alice
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testNotInMyselfUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to NOT IN(MYSELF(), 'bob')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->alice
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testNotInMultipleUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to NOT IN('bob', 'alice')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testMultipleNotIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@assigned_to NOT IN('bob') AND @assigned_to NOT IN('alice')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }
}
