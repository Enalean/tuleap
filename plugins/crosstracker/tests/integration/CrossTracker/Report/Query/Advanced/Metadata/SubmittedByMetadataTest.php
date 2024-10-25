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
use Tuleap\CrossTracker\CrossTrackerDefaultReport;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Tests\Report\ArtifactReportFactoryInstantiator;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\REST\v1\ArtifactMatchingReportCollection;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;
use UserManager;

final class SubmittedByMetadataTest extends CrossTrackerFieldTestCase
{
    private PFUser $project_member;
    private PFUser $project_admin;
    private PFUser $alice;
    private Tracker $release_tracker;
    private Tracker $sprint_tracker;
    private Tracker $task_tracker;
    private int $release_artifact_alice_id;
    private int $release_artifact_bob_id;
    private int $sprint_artifact_alice_id;
    private int $sprint_artifact_bob_id;
    private int $sprint_artifact_charles_id;
    private int $task_artifact_alice_id;
    private int $task_artifact_bob_id;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project              = $core_builder->buildProject('project_name');
        $project_id           = (int) $project->getID();
        $this->project_member = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $this->project_admin  = $core_builder->buildUser('project_admin', 'Project Admin', 'project_admin@example.com');
        $core_builder->addUserToProjectMembers((int) $this->project_member->getId(), $project_id);
        $core_builder->addUserToProjectMembers((int) $this->project_admin->getId(), $project_id);
        $core_builder->addUserToProjectAdmins((int) $this->project_admin->getId(), $project_id);

        $this->alice = $core_builder->buildUser('alice', 'Alice', 'alice@example.com');
        $bob         = $core_builder->buildUser('bob', 'Bob', 'bob@example.com');
        $charles     = $core_builder->buildUser('charles', 'Charles', 'charles@example.com');
        $core_builder->addUserToProjectMembers((int) $this->alice->getId(), $project_id);
        $core_builder->addUserToProjectMembers((int) $bob->getId(), $project_id);

        $user_manager = $this->createPartialMock(UserManager::class, ['getCurrentUser']);
        $user_manager->method('getCurrentUser')->willReturn($this->alice);
        UserManager::setInstance($user_manager);

        $this->release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $this->sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $this->task_tracker    = $tracker_builder->buildTracker($project_id, 'Task');

        $task_subby_field_id = $tracker_builder->buildSubmittedByField($this->task_tracker->getId());
        $tracker_builder->grantReadPermissionOnField($task_subby_field_id, ProjectUGroup::PROJECT_ADMIN);

        $this->release_artifact_alice_id  = $tracker_builder->buildArtifact($this->release_tracker->getId(), 0, (int) $this->alice->getId());
        $this->release_artifact_bob_id    = $tracker_builder->buildArtifact($this->release_tracker->getId(), 0, (int) $bob->getId());
        $this->sprint_artifact_alice_id   = $tracker_builder->buildArtifact($this->sprint_tracker->getId(), 0, (int) $this->alice->getId());
        $this->sprint_artifact_bob_id     = $tracker_builder->buildArtifact($this->sprint_tracker->getId(), 0, (int) $bob->getId());
        $this->sprint_artifact_charles_id = $tracker_builder->buildArtifact($this->sprint_tracker->getId(), 0, (int) $charles->getId());
        $this->task_artifact_alice_id     = $tracker_builder->buildArtifact($this->task_tracker->getId(), 0, (int) $this->alice->getId());
        $this->task_artifact_bob_id       = $tracker_builder->buildArtifact($this->task_tracker->getId(), 0, (int) $bob->getId());

        $tracker_builder->buildLastChangeset($this->release_artifact_alice_id);
        $tracker_builder->buildLastChangeset($this->release_artifact_bob_id);
        $tracker_builder->buildLastChangeset($this->sprint_artifact_alice_id);
        $tracker_builder->buildLastChangeset($this->sprint_artifact_bob_id);
        $tracker_builder->buildLastChangeset($this->sprint_artifact_charles_id);
        $tracker_builder->buildLastChangeset($this->task_artifact_alice_id);
        $tracker_builder->buildLastChangeset($this->task_artifact_bob_id);
    }

    /**
     * @return list<int>
     * @throws SearchablesDoNotExistException
     * @throws SearchablesAreInvalidException
     */
    private function getMatchingArtifactIds(CrossTrackerDefaultReport $report, PFUser $user): array
    {
        $result = (new ArtifactReportFactoryInstantiator())
            ->getFactory()
            ->getArtifactsMatchingReport($report, $user, 10, 0);
        assert($result instanceof ArtifactMatchingReportCollection);
        return array_values(array_map(static fn(Artifact $artifact) => $artifact->getId(), $result->getArtifacts()));
    }

    public function testEqualUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                "@submitted_by = 'bob'",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_member,
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_bob_id, $this->sprint_artifact_bob_id], $artifacts);
    }

    public function testPermissionsEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                "@submitted_by = 'bob'",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_admin,
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_bob_id, $this->sprint_artifact_bob_id, $this->task_artifact_bob_id], $artifacts);
    }

    public function testEqualMyself(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                '@submitted_by = MYSELF()',
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->alice,
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_alice_id, $this->sprint_artifact_alice_id], $artifacts);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                "@submitted_by = 'bob' OR @submitted_by = 'alice'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member,
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_alice_id, $this->release_artifact_bob_id,
            $this->sprint_artifact_alice_id, $this->sprint_artifact_bob_id,
        ], $artifacts);
    }

    public function testNotEqualUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                "@submitted_by != 'bob'",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_member,
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_alice_id, $this->sprint_artifact_alice_id, $this->sprint_artifact_charles_id], $artifacts);
    }

    public function testPermissionsNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                "@submitted_by != 'bob'",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_admin,
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_alice_id,
            $this->sprint_artifact_alice_id, $this->sprint_artifact_charles_id,
            $this->task_artifact_alice_id,
        ], $artifacts);
    }

    public function testNotEqualMyself(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                '@submitted_by != MYSELF()',
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->alice,
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_bob_id, $this->sprint_artifact_bob_id, $this->sprint_artifact_charles_id], $artifacts);
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                "@submitted_by != 'bob' AND @submitted_by != 'alice'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member,
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_charles_id], $artifacts);
    }

    public function testInUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                "@submitted_by IN('bob')",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_member,
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_bob_id, $this->sprint_artifact_bob_id], $artifacts);
    }

    public function testPermissionsIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                "@submitted_by IN('bob')",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_admin,
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_bob_id, $this->sprint_artifact_bob_id, $this->task_artifact_bob_id], $artifacts);
    }

    public function testInMyself(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                '@submitted_by IN(MYSELF())',
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->alice,
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_alice_id, $this->sprint_artifact_alice_id], $artifacts);
    }

    public function testInMultiple(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                "@submitted_by IN('bob', MYSELF())",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->alice,
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_bob_id, $this->release_artifact_alice_id,
            $this->sprint_artifact_bob_id, $this->sprint_artifact_alice_id,
        ], $artifacts);
    }

    public function testMultipleIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                "@submitted_by IN('bob') OR @submitted_by IN('charles')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member,
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_bob_id, $this->sprint_artifact_bob_id, $this->sprint_artifact_charles_id], $artifacts);
    }

    public function testNotInUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                "@submitted_by NOT IN('bob')",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_member,
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_alice_id, $this->sprint_artifact_alice_id, $this->sprint_artifact_charles_id], $artifacts);
    }

    public function testPermissionsNotIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                "@submitted_by NOT IN('bob')",
                [$this->release_tracker, $this->sprint_tracker, $this->task_tracker],
            ),
            $this->project_admin,
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_alice_id,
            $this->sprint_artifact_alice_id, $this->sprint_artifact_charles_id,
            $this->task_artifact_alice_id,
        ], $artifacts);
    }

    public function testNotInMyself(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                '@submitted_by NOT IN(MYSELF())',
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->alice,
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_bob_id, $this->sprint_artifact_bob_id, $this->sprint_artifact_charles_id], $artifacts);
    }

    public function testNotInMultiple(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                "@submitted_by NOT IN('bob', 'alice')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->alice,
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_charles_id], $artifacts);
    }

    public function testMultipleNotIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerDefaultReport(
                1,
                "@submitted_by NOT IN('bob') AND @submitted_by NOT IN('alice')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member,
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_charles_id], $artifacts);
    }
}
