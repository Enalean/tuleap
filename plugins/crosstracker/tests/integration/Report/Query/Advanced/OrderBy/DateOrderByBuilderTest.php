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

namespace Tuleap\CrossTracker\Report\Query\Advanced\OrderBy;

use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerQuery;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\NumericResultRepresentation;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class DateOrderByBuilderTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $user;
    /** @var list<int> */
    private array $result_descending;
    /** @var list<int> */
    private array $result_ascending;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getId();
        $this->user = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_id);
        $this->uuid = $this->addReportToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $date_field_id = $tracker_builder->buildDateField($release_tracker->getId(), 'date_field', false);
        $tracker_builder->grantReadPermissionOnField($date_field_id, ProjectUGroup::PROJECT_MEMBERS);

        $artifact_1 = $tracker_builder->buildArtifact($release_tracker->getId());
        $artifact_2 = $tracker_builder->buildArtifact($release_tracker->getId());
        $artifact_3 = $tracker_builder->buildArtifact($release_tracker->getId());

        $changeset_1 = $tracker_builder->buildLastChangeset($artifact_1);
        $changeset_2 = $tracker_builder->buildLastChangeset($artifact_2);
        $changeset_3 = $tracker_builder->buildLastChangeset($artifact_3);

        $tracker_builder->buildDateValue($changeset_1, $date_field_id, 2);
        $tracker_builder->buildDateValue($changeset_2, $date_field_id, 1);
        $tracker_builder->buildDateValue($changeset_3, $date_field_id, 3);

        $this->result_descending = [$artifact_3, $artifact_1, $artifact_2];
        $this->result_ascending  = [$artifact_2, $artifact_1, $artifact_3];
    }

    public function testLastUpdateDateDescending(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerQuery($this->uuid, 'SELECT @id FROM @project = "self" WHERE @id >= 1 ORDER BY date_field DESC', '', '', 1),
            $this->user,
        );

        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('@id', $artifact);
            $value = $artifact['@id'];
            self::assertInstanceOf(NumericResultRepresentation::class, $value);
            $values[] = $value->value;
        }
        self::assertSame($this->result_descending, $values);
    }

    public function testLastUpdateDateAscending(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerQuery($this->uuid, 'SELECT @id FROM @project = "self" WHERE @id >= 1 ORDER BY date_field ASC', '', '', 1),
            $this->user,
        );

        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('@id', $artifact);
            $value = $artifact['@id'];
            self::assertInstanceOf(NumericResultRepresentation::class, $value);
            $values[] = $value->value;
        }
        self::assertSame($this->result_ascending, $values);
    }
}
