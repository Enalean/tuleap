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

namespace Tuleap\CrossTracker\Query\Advanced\Metadata;

use PFUser;
use ProjectUGroup;
use Tracker_FormElement_Field_List;
use Tuleap\CrossTracker\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Tests\CrossTrackerQueryTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;
use Tuleap\Tracker\Tracker;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AssignedToMetadataTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $project_member;
    private PFUser $project_admin;
    private PFUser $alice;
    private int $release_artifact_empty_id;
    private int $release_artifact_with_alice_id;
    private int $sprint_artifact_empty_id;
    private int $sprint_artifact_with_alice_bob_id;
    private int $task_artifact_with_alice_id;

    #[\Override]
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
        $this->uuid = $this->addWidgetToProject(1, $project_id);

        $this->alice = $core_builder->buildUser('alice', 'Alice', 'alice@example.com');
        $bob         = $core_builder->buildUser('bob', 'Bob', 'bob@example.com');
        $core_builder->addUserToProjectMembers((int) $this->alice->getId(), $project_id);
        $core_builder->addUserToProjectMembers((int) $bob->getId(), $project_id);

        $user_manager = $this->createPartialMock(UserManager::class, ['getCurrentUser']);
        $user_manager->method('getCurrentUser')->willReturn($this->alice);
        UserManager::setInstance($user_manager);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $task_tracker    = $tracker_builder->buildTracker($project_id, 'Task');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($task_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $release_assignee_field_id = $tracker_builder->buildUserListField($release_tracker->getId(), 'release_assignee', 'sb');
        $sprint_assignee_field_id  = $tracker_builder->buildUserListField($sprint_tracker->getId(), 'sprint_assignee', 'msb');
        $task_assignee_field_id    = $tracker_builder->buildUserListField($task_tracker->getId(), 'task_assignee', 'sb');

        $tracker_builder->buildContributorAssigneeSemantic($release_tracker->getId(), $release_assignee_field_id);
        $tracker_builder->buildContributorAssigneeSemantic($sprint_tracker->getId(), $sprint_assignee_field_id);
        $tracker_builder->buildContributorAssigneeSemantic($task_tracker->getId(), $task_assignee_field_id);

        $tracker_builder->grantReadPermissionOnField(
            $release_assignee_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $sprint_assignee_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $task_assignee_field_id,
            ProjectUGroup::PROJECT_ADMIN
        );

        $this->release_artifact_empty_id         = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->release_artifact_with_alice_id    = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->sprint_artifact_empty_id          = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->sprint_artifact_with_alice_bob_id = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->task_artifact_with_alice_id       = $tracker_builder->buildArtifact($task_tracker->getId());

        $release_artifact_empty_changeset         = $tracker_builder->buildLastChangeset($this->release_artifact_empty_id);
        $release_artifact_with_alice_changeset    = $tracker_builder->buildLastChangeset($this->release_artifact_with_alice_id);
        $sprint_artifact_empty_changeset          = $tracker_builder->buildLastChangeset($this->sprint_artifact_empty_id);
        $sprint_artifact_with_alice_bob_changeset = $tracker_builder->buildLastChangeset($this->sprint_artifact_with_alice_bob_id);
        $task_artifact_with_alice_changeset       = $tracker_builder->buildLastChangeset($this->task_artifact_with_alice_id);

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
        $tracker_builder->buildListValue(
            $task_artifact_with_alice_changeset,
            $task_assignee_field_id,
            (int) $this->alice->getId(),
        );
    }

    public function testEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to = ''",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testEqualUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to = 'alice'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testPermissionsEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to = 'alice'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id, $this->task_artifact_with_alice_id], $artifacts);
    }

    public function testEqualMyself(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to = MYSELF()",
                )->build(),
            $this->alice
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to = 'alice' AND @assigned_to = 'bob'",
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testNotEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to != ''",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testNotEqualUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to != 'bob'",
                )->build(),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->release_artifact_with_alice_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testPermissionsNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to != 'bob'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_alice_id,
            $this->sprint_artifact_empty_id,
            $this->task_artifact_with_alice_id,
        ], $artifacts);
    }

    public function testNotEqualMyself(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to != MYSELF()",
                )->build(),
            $this->alice
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to != 'alice' AND @assigned_to != 'bob'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testInUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to IN('alice')",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testPermissionsIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to IN('alice')",
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id, $this->task_artifact_with_alice_id], $artifacts);
    }

    public function testInMyself(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to IN(MYSELF())",
                )->build(),
            $this->alice
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testInMyselfUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to IN(MYSELF(), 'bob')",
                )->build(),
            $this->alice
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testInMultipleUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to IN('alice', 'bob')",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testMultipleIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to IN('alice') OR @assigned_to IN('bob')",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_alice_id, $this->sprint_artifact_with_alice_bob_id], $artifacts);
    }

    public function testNotInUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to NOT IN('bob')",
                )->build(),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->release_artifact_with_alice_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testPermissionsNotIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to NOT IN('bob')",
                )->build(),
            $this->project_admin
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_alice_id,
            $this->sprint_artifact_empty_id,
            $this->task_artifact_with_alice_id,
        ], $artifacts);
    }

    public function testNotInMyself(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to NOT IN(MYSELF())",
                )->build(),
            $this->alice
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testNotInMyselfUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to NOT IN(MYSELF(), 'bob')",
                )->build(),
            $this->alice
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testNotInMultipleUser(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to NOT IN('bob', 'alice')",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testMultipleNotIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @assigned_to NOT IN('bob') AND @assigned_to NOT IN('alice')",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }
}
