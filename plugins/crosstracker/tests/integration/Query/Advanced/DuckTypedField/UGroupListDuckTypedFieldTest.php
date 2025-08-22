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

namespace Tuleap\CrossTracker\Query\Advanced\DuckTypedField;

use BaseLanguageFactory;
use PFUser;
use ProjectUGroup;
use Tuleap\CrossTracker\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Tests\CrossTrackerQueryTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UGroupListDuckTypedFieldTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $project_member;
    private PFUser $project_admin;
    private int $release_artifact_empty_id;
    private int $release_artifact_with_members_id;
    private int $sprint_artifact_empty_id;
    private int $sprint_artifact_with_members_static_id;
    private int $task_artifact_with_members_id;

    #[\Override]
    protected function setUp(): void
    {
        $GLOBALS['Language'] = (new BaseLanguageFactory())->getBaseLanguage('en_US');

        $db                   = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder      = new TrackerDatabaseBuilder($db);
        $core_builder         = new CoreDatabaseBuilder($db);
        $project              = $core_builder->buildProject('project_name');
        $project_id           = (int) $project->getID();
        $this->project_member = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $this->project_admin  = $core_builder->buildUser('project_admin', 'Project Admin', 'project_admin@example.com');
        $core_builder->addUserToProjectMembers((int) $this->project_member->getId(), $project_id);
        $core_builder->addUserToProjectMembers((int) $this->project_admin->getId(), $project_id);
        $core_builder->addUserToProjectAdmins((int) $this->project_admin->getId(), $project_id);
        $this->uuid = $this->addWidgetToProject(1, $project_id);

        $static_ugroup_id = $core_builder->buildStaticUserGroup($project_id, 'MyStaticUGroup');

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $task_tracker    = $tracker_builder->buildTracker($project_id, 'Task');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($task_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $release_ugroup_field_id = $tracker_builder->buildUserGroupListField($release_tracker->getId(), 'ugroup_field', 'sb');
        $release_bind_ids        = $tracker_builder->buildValuesForUserGroupListField($release_ugroup_field_id, [
            ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN, $static_ugroup_id,
        ]);
        $sprint_ugroup_field_id  = $tracker_builder->buildUserGroupListField($sprint_tracker->getId(), 'ugroup_field', 'msb');
        $sprint_bind_ids         = $tracker_builder->buildValuesForUserGroupListField($sprint_ugroup_field_id, [
            ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN, $static_ugroup_id,
        ]);
        $task_ugroup_field_id    = $tracker_builder->buildUserGroupListField($task_tracker->getId(), 'ugroup_field', 'sb');
        $task_bind_ids           = $tracker_builder->buildValuesForUserGroupListField($task_ugroup_field_id, [
            ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN, $static_ugroup_id,
        ]);

        $tracker_builder->grantReadPermissionOnField(
            $release_ugroup_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $sprint_ugroup_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $task_ugroup_field_id,
            ProjectUGroup::PROJECT_ADMIN
        );

        $this->release_artifact_empty_id              = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->release_artifact_with_members_id       = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->sprint_artifact_empty_id               = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->sprint_artifact_with_members_static_id = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->task_artifact_with_members_id          = $tracker_builder->buildArtifact($task_tracker->getId());

        $release_artifact_empty_changeset              = $tracker_builder->buildLastChangeset($this->release_artifact_empty_id);
        $release_artifact_with_members_changeset       = $tracker_builder->buildLastChangeset($this->release_artifact_with_members_id);
        $sprint_artifact_empty_changeset               = $tracker_builder->buildLastChangeset($this->sprint_artifact_empty_id);
        $sprint_artifact_with_members_static_changeset = $tracker_builder->buildLastChangeset($this->sprint_artifact_with_members_static_id);
        $task_artifact_with_members_changeset          = $tracker_builder->buildLastChangeset($this->task_artifact_with_members_id);

        $tracker_builder->buildListValue(
            $release_artifact_empty_changeset,
            $release_ugroup_field_id,
            ListField::NONE_VALUE,
        );
        $tracker_builder->buildListValue(
            $release_artifact_with_members_changeset,
            $release_ugroup_field_id,
            $release_bind_ids[ProjectUGroup::PROJECT_MEMBERS],
        );
        $tracker_builder->buildListValue(
            $sprint_artifact_empty_changeset,
            $sprint_ugroup_field_id,
            ListField::NONE_VALUE,
        );
        $tracker_builder->buildListValue(
            $sprint_artifact_with_members_static_changeset,
            $sprint_ugroup_field_id,
            $sprint_bind_ids[ProjectUGroup::PROJECT_MEMBERS],
        );
        $tracker_builder->buildListValue(
            $sprint_artifact_with_members_static_changeset,
            $sprint_ugroup_field_id,
            $sprint_bind_ids[$static_ugroup_id],
        );
        $tracker_builder->buildListValue(
            $task_artifact_with_members_changeset,
            $task_ugroup_field_id,
            $task_bind_ids[ProjectUGroup::PROJECT_MEMBERS],
        );
    }

    public function testEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field = ''",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testEqualUGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field = 'Project members'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_members_id, $this->sprint_artifact_with_members_static_id], $artifacts);
    }

    public function testPermissionsEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field = 'Project members'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_members_id, $this->sprint_artifact_with_members_static_id, $this->task_artifact_with_members_id], $artifacts);
    }

    public function testEqualStaticUGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field = 'MyStaticUGroup'",
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_with_members_static_id], $artifacts);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field = 'MyStaticUGroup' AND ugroup_field = 'Project members'",
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_with_members_static_id], $artifacts);
    }

    public function testNotEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field != ''",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_members_id, $this->sprint_artifact_with_members_static_id], $artifacts);
    }

    public function testNotEqualUGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field != 'Project administrators'",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_members_id,
            $this->sprint_artifact_empty_id, $this->sprint_artifact_with_members_static_id,
        ], $artifacts);
    }

    public function testPermissionsNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field != 'Project administrators'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(5, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_members_id,
            $this->sprint_artifact_empty_id, $this->sprint_artifact_with_members_static_id,
            $this->task_artifact_with_members_id,
        ], $artifacts);
    }

    public function testNotEqualStaticGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field != 'MyStaticUGroup'",
                )->build(),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_members_id,
            $this->sprint_artifact_empty_id,
        ], $artifacts);
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field != 'MyStaticUGroup' AND ugroup_field != 'Project members'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testInUGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field IN('Project members')",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_members_id, $this->sprint_artifact_with_members_static_id], $artifacts);
    }

    public function testPermissionsIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field IN('Project members')",
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_members_id, $this->sprint_artifact_with_members_static_id, $this->task_artifact_with_members_id], $artifacts);
    }

    public function testInMultipleUGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field IN('MyStaticUGroup', 'Project members')",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_members_id, $this->sprint_artifact_with_members_static_id], $artifacts);
    }

    public function testMultipleIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field IN('MyStaticUGroup') AND ugroup_field IN('Project members')",
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_with_members_static_id], $artifacts);
    }

    public function testNotInUGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field NOT IN('MyStaticUGroup')",
                )->build(),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->release_artifact_with_members_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testPermissionsNotIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field NOT IN('MyStaticUGroup')",
                )->build(),
            $this->project_admin
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_members_id,
            $this->sprint_artifact_empty_id,
            $this->task_artifact_with_members_id,
        ], $artifacts);
    }

    public function testNotInMultipleUGroup(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field NOT IN('MyStaticUGroup', 'Project members')",
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
                    "SELECT @id FROM @project = 'self' WHERE ugroup_field NOT IN('MyStaticUGroup') AND ugroup_field NOT IN('Project members')",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }
}
