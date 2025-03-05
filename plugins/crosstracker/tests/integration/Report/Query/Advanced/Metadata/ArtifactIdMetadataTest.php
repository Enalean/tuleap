<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Tests\CrossTrackerQueryTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactIdMetadataTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $project_member;
    private PFUser $project_admin;
    private int $sprint_artifact_1_id;
    private int $release_artifact_1_id;
    private int $release_artifact_2_id;
    private int $release_artifact_3_id;
    private int $sprint_artifact_2_id;

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
        $this->uuid = $this->addReportToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $sprint_artifact_id_field_id = $tracker_builder->buildArtifactIdField($sprint_tracker->getId());
        $tracker_builder->grantReadPermissionOnField($sprint_artifact_id_field_id, ProjectUGroup::PROJECT_ADMIN);

        $this->sprint_artifact_1_id  = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->release_artifact_1_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->release_artifact_2_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->release_artifact_3_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->sprint_artifact_2_id  = $tracker_builder->buildArtifact($sprint_tracker->getId());

        // Build a last changeset for each artifact, otherwise they won't be found
        $tracker_builder->buildLastChangeset($this->sprint_artifact_1_id);
        $tracker_builder->buildLastChangeset($this->release_artifact_1_id);
        $tracker_builder->buildLastChangeset($this->release_artifact_2_id);
        $tracker_builder->buildLastChangeset($this->release_artifact_3_id);
        $tracker_builder->buildLastChangeset($this->sprint_artifact_2_id);
    }

    public function testEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id = %d', $this->release_artifact_1_id),
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertSame($this->release_artifact_1_id, $artifacts[0]);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id = %d OR @id = %d', $this->release_artifact_1_id, $this->sprint_artifact_1_id),
                )->build(),
            $this->project_admin
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_1_id, $this->sprint_artifact_1_id], $artifacts);
    }

    public function testPermissionsEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id = %d', $this->sprint_artifact_1_id),
                )->build(),
            $this->project_member
        );

        self::assertCount(0, $artifacts);
    }

    public function testNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id != %d', $this->release_artifact_1_id),
                )->build(),
            $this->project_admin
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_1_id, $this->release_artifact_3_id, $this->release_artifact_2_id, $this->sprint_artifact_2_id], $artifacts);
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id != %d AND @id != %d', $this->release_artifact_1_id, $this->sprint_artifact_1_id),
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_3_id, $this->release_artifact_2_id, $this->sprint_artifact_2_id], $artifacts);
    }

    public function testPermissionsNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id != %d', $this->release_artifact_1_id),
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_2_id, $this->release_artifact_3_id], $artifacts);
    }

    public function testLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id < %d', $this->release_artifact_3_id),
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_2_id, $this->release_artifact_1_id, $this->sprint_artifact_1_id], $artifacts);
    }

    public function testMultipleLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id < %d AND @id < %d', $this->release_artifact_2_id, $this->release_artifact_3_id),
                )->build(),
            $this->project_admin
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_1_id, $this->sprint_artifact_1_id], $artifacts);
    }

    public function testPermissionsLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id < %d', $this->release_artifact_3_id),
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_2_id, $this->release_artifact_1_id], $artifacts);
    }

    public function testLesserThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id <= %d', $this->release_artifact_2_id),
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_2_id, $this->release_artifact_1_id, $this->sprint_artifact_1_id], $artifacts);
    }

    public function testMultipleLesserThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id <= %d AND @id <= %d', $this->release_artifact_1_id, $this->release_artifact_2_id),
                )->build(),
            $this->project_admin
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_1_id, $this->sprint_artifact_1_id], $artifacts);
    }

    public function testPermissionsLesserThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id <= %d', $this->release_artifact_2_id),
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_2_id, $this->release_artifact_1_id], $artifacts);
    }

    public function testGreaterThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id > %d', $this->release_artifact_1_id),
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_2_id, $this->release_artifact_3_id, $this->sprint_artifact_2_id], $artifacts);
    }

    public function testMultipleGreaterThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id > %d AND @id > %d', $this->release_artifact_1_id, $this->release_artifact_2_id),
                )->build(),
            $this->project_admin
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_3_id, $this->sprint_artifact_2_id], $artifacts);
    }

    public function testPermissionsGreaterThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id > %d', $this->release_artifact_1_id),
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_2_id, $this->release_artifact_3_id], $artifacts);
    }

    public function testGreaterThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id >= %d', $this->release_artifact_2_id),
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_2_id, $this->release_artifact_3_id, $this->sprint_artifact_2_id], $artifacts);
    }

    public function testMultipleGreaterThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id >= %d AND @id >= %d', $this->release_artifact_1_id, $this->release_artifact_2_id),
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_2_id, $this->release_artifact_3_id, $this->sprint_artifact_2_id], $artifacts);
    }

    public function testPermissionsGreaterThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id >= %d', $this->release_artifact_1_id),
                )->build(),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_1_id, $this->release_artifact_2_id, $this->release_artifact_3_id], $artifacts);
    }

    public function testBetween(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id BETWEEN(%d, %d)', $this->sprint_artifact_1_id, $this->sprint_artifact_2_id),
                )->build(),
            $this->project_admin
        );

        self::assertCount(5, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->sprint_artifact_1_id,
            $this->release_artifact_1_id,
            $this->release_artifact_2_id,
            $this->release_artifact_3_id,
            $this->sprint_artifact_2_id,
        ], $artifacts);
    }

    public function testMultipleBetween(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf(
                        'SELECT @id FROM @project = "self" WHERE %s OR %s',
                        sprintf('@id BETWEEN(%d, %d)', $this->sprint_artifact_1_id, $this->release_artifact_1_id),
                        sprintf('@id BETWEEN(%d, %d)', $this->release_artifact_3_id, $this->sprint_artifact_2_id),
                    ),
                )->build(),
            $this->project_admin
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->sprint_artifact_1_id,
            $this->release_artifact_1_id,
            $this->release_artifact_3_id,
            $this->sprint_artifact_2_id,
        ], $artifacts);
    }

    public function testPermissionsBetween(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    sprintf('SELECT @id FROM @project = "self" WHERE @id BETWEEN(%d, %d)', $this->sprint_artifact_1_id, $this->sprint_artifact_2_id),
                )->build(),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_1_id,
            $this->release_artifact_2_id,
            $this->release_artifact_3_id,
        ], $artifacts);
    }
}
