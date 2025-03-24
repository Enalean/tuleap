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

use PFUser;
use ProjectUGroup;
use Tracker;
use Tracker_FormElement_Field_List;
use Tuleap\CrossTracker\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Tests\CrossTrackerQueryTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OpenStaticListDuckTypedFieldTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $project_member;
    private int $release_artifact_empty_id;
    private int $release_artifact_with_cheese_id;
    private int $release_artifact_with_lead_id;
    private int $sprint_artifact_empty_id;
    private int $sprint_artifact_with_cheese_id;
    private int $sprint_artifact_with_cheese_lead_id;

    protected function setUp(): void
    {
        $db                   = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder      = new TrackerDatabaseBuilder($db);
        $core_builder         = new CoreDatabaseBuilder($db);
        $project              = $core_builder->buildProject('project_name');
        $project_id           = (int) $project->getID();
        $this->project_member = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->project_member->getId(), $project_id);
        $this->uuid = $this->addWidgetToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $release_list_field_id = $tracker_builder->buildStaticListField($release_tracker->getId(), 'list_field', 'sb');
        $release_bind_ids      = $tracker_builder->buildValuesForStaticListField($release_list_field_id, ['lead', 'management', 'cheese']);
        $sprint_list_field_id  = $tracker_builder->buildStaticListField($sprint_tracker->getId(), 'list_field', 'tbl');
        $sprint_bind_ids       = $tracker_builder->buildValuesForStaticListField($sprint_list_field_id, ['lead', 'management']);
        $sprint_open_bind_ids  = $tracker_builder->buildValuesForStaticOpenListField($sprint_list_field_id, ['cheese']);

        $tracker_builder->grantReadPermissionOnField(
            $release_list_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $sprint_list_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $this->release_artifact_empty_id           = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->release_artifact_with_cheese_id     = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->release_artifact_with_lead_id       = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->sprint_artifact_empty_id            = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->sprint_artifact_with_cheese_id      = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->sprint_artifact_with_cheese_lead_id = $tracker_builder->buildArtifact($sprint_tracker->getId());

        $release_artifact_empty_changeset           = $tracker_builder->buildLastChangeset($this->release_artifact_empty_id);
        $release_artifact_with_cheese_changeset     = $tracker_builder->buildLastChangeset($this->release_artifact_with_cheese_id);
        $release_artifact_with_lead_changeset       = $tracker_builder->buildLastChangeset($this->release_artifact_with_lead_id);
        $sprint_artifact_empty_changeset            = $tracker_builder->buildLastChangeset($this->sprint_artifact_empty_id);
        $sprint_artifact_with_cheese_changeset      = $tracker_builder->buildLastChangeset($this->sprint_artifact_with_cheese_id);
        $sprint_artifact_with_cheese_lead_changeset = $tracker_builder->buildLastChangeset($this->sprint_artifact_with_cheese_lead_id);

        $tracker_builder->buildListValue(
            $release_artifact_empty_changeset,
            $release_list_field_id,
            Tracker_FormElement_Field_List::NONE_VALUE
        );
        $tracker_builder->buildListValue(
            $release_artifact_with_cheese_changeset,
            $release_list_field_id,
            $release_bind_ids['cheese']
        );
        $tracker_builder->buildListValue(
            $release_artifact_with_lead_changeset,
            $release_list_field_id,
            $release_bind_ids['lead']
        );
        $tracker_builder->buildOpenValue(
            $sprint_artifact_empty_changeset,
            $sprint_list_field_id,
            Tracker_FormElement_Field_List::NONE_VALUE,
            false,
        );
        $tracker_builder->buildOpenValue(
            $sprint_artifact_with_cheese_changeset,
            $sprint_list_field_id,
            $sprint_open_bind_ids['cheese'],
            true,
        );
        $tracker_builder->buildOpenValue(
            $sprint_artifact_with_cheese_lead_changeset,
            $sprint_list_field_id,
            $sprint_open_bind_ids['cheese'],
            true,
        );
        $tracker_builder->buildOpenValue(
            $sprint_artifact_with_cheese_lead_changeset,
            $sprint_list_field_id,
            $sprint_bind_ids['lead'],
            false,
        );
    }

    public function testEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE list_field = ''",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testEqualValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE list_field = 'cheese'",
                )->build(),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_lead_id], $artifacts);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE list_field = 'cheese' OR list_field = 'lead'",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_cheese_id, $this->release_artifact_with_lead_id,
            $this->sprint_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_lead_id,
        ], $artifacts);
    }

    public function testMultipleEqualAnd(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE list_field = 'cheese' AND list_field = 'lead'",
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_with_cheese_lead_id], $artifacts);
    }

    public function testNotEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE list_field != ''",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_cheese_id, $this->release_artifact_with_lead_id,
            $this->sprint_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_lead_id,
        ], $artifacts);
    }

    public function testNotEqualValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE list_field != 'lead'",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_cheese_id,
            $this->sprint_artifact_empty_id, $this->sprint_artifact_with_cheese_id,
        ], $artifacts);
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE list_field != 'cheese' AND list_field != 'lead'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testInValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE list_field IN('cheese')",
                )->build(),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_lead_id,], $artifacts);
    }

    public function testInValues(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE list_field IN('lead', 'cheese')",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_cheese_id, $this->release_artifact_with_lead_id,
            $this->sprint_artifact_with_cheese_id, $this->sprint_artifact_with_cheese_lead_id,
        ], $artifacts);
    }

    public function testMultipleIn(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE list_field IN('lead') AND list_field IN('cheese')",
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_with_cheese_lead_id], $artifacts);
    }

    public function testNotInValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE list_field NOT IN('lead')",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_cheese_id,
            $this->sprint_artifact_empty_id, $this->sprint_artifact_with_cheese_id,
        ], $artifacts);
    }

    public function testNotInValues(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE list_field NOT IN('lead', 'cheese')",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testMultipleNotInValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE list_field NOT IN('lead') AND list_field NOT IN ('cheese')",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }
}
