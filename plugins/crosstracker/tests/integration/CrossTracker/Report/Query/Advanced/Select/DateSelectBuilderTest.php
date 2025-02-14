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

use DateTime;
use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerQuery;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\DateResultRepresentation;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class DateSelectBuilderTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    /**
     * @var array<int, ?int>
     */
    private array $expected_values;
    private PFUser $user;

    public function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $this->user = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_id);
        $this->uuid = $this->addReportToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $release_date_field_id = $tracker_builder->buildDateField(
            $release_tracker->getId(),
            'date_field',
            false
        );
        $sprint_date_field_id  = $tracker_builder->buildDateField(
            $sprint_tracker->getId(),
            'date_field',
            false
        );

        $tracker_builder->grantReadPermissionOnField(
            $release_date_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $sprint_date_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_artifact_empty_id      = $tracker_builder->buildArtifact($release_tracker->getId());
        $release_artifact_with_date_id  = $tracker_builder->buildArtifact($release_tracker->getId());
        $release_artifact_with_now_id   = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_empty_id       = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $sprint_artifact_with_date_id   = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $sprint_artifact_with_future_id = $tracker_builder->buildArtifact($sprint_tracker->getId());

        $tracker_builder->buildLastChangeset($release_artifact_empty_id);
        $release_artifact_with_date_changeset = $tracker_builder->buildLastChangeset($release_artifact_with_date_id);
        $release_artifact_with_now_changeset  = $tracker_builder->buildLastChangeset($release_artifact_with_now_id);
        $tracker_builder->buildLastChangeset($sprint_artifact_empty_id);
        $sprint_artifact_with_date_changeset   = $tracker_builder->buildLastChangeset($sprint_artifact_with_date_id);
        $sprint_artifact_with_future_changeset = $tracker_builder->buildLastChangeset($sprint_artifact_with_future_id);

        $this->expected_values = [
            $release_artifact_empty_id      => null,
            $release_artifact_with_date_id  => (new DateTime('2023-02-12'))->getTimestamp(),
            $release_artifact_with_now_id   => (new DateTime())->setTime(0, 0)->getTimestamp(),
            $sprint_artifact_empty_id       => null,
            $sprint_artifact_with_date_id   => (new DateTime('2023-03-12'))->getTimestamp(),
            $sprint_artifact_with_future_id => (new DateTime('tomorrow'))->getTimestamp(),
        ];
        $tracker_builder->buildDateValue(
            $release_artifact_with_date_changeset,
            $release_date_field_id,
            (int) $this->expected_values[$release_artifact_with_date_id],
        );
        $tracker_builder->buildDateValue(
            $release_artifact_with_now_changeset,
            $release_date_field_id,
            (int) $this->expected_values[$release_artifact_with_now_id],
        );
        $tracker_builder->buildDateValue(
            $sprint_artifact_with_date_changeset,
            $sprint_date_field_id,
            (int) $this->expected_values[$sprint_artifact_with_date_id],
        );
        $tracker_builder->buildDateValue(
            $sprint_artifact_with_future_changeset,
            $sprint_date_field_id,
            (int) $this->expected_values[$sprint_artifact_with_future_id],
        );
    }

    public function testItReturnsColumns(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT date_field FROM @project = 'self' WHERE date_field = '' OR date_field != ''",
                '',
                '',
                1
            ),
            $this->user,
        );

        self::assertSame(6, $result->getTotalSize());
        self::assertCount(2, $result->selected);
        self::assertSame('date_field', $result->selected[1]->name);
        self::assertSame('date', $result->selected[1]->type);
        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('date_field', $artifact);
            $value = $artifact['date_field'];
            self::assertInstanceOf(DateResultRepresentation::class, $value);
            $values[] = $value->value;
        }
        self::assertEqualsCanonicalizing(array_map(
            static fn(?int $value) => $value === null ? null : (new DateTime("@$value"))->format(DateTime::ATOM),
            array_values($this->expected_values)
        ), $values);
    }
}
