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
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\StaticListRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\StaticListValueRepresentation;
use Tuleap\CrossTracker\Tests\CrossTrackerQueryTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StatusSelectBuilderTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $user;
    /**
     * @var array<int, StaticListValueRepresentation[]>
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
        $this->uuid = $this->addReportToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $release_status_field_id = $tracker_builder->buildStaticListField($release_tracker->getId(), 'field_status', 'sb');
        $release_status_values   = $tracker_builder->buildOpenAndClosedValuesForField($release_status_field_id, $release_tracker->getId(), ['Open'], ['Closed']);
        $sprint_status_field_id  = $tracker_builder->buildStaticListField($sprint_tracker->getId(), 'field_status', 'sb');
        $sprint_status_values    = $tracker_builder->buildOpenAndClosedValuesForField($sprint_status_field_id, $sprint_tracker->getId(), ['Open'], ['Closed', 'Also closed']);

        $tracker_builder->grantReadPermissionOnField(
            $release_status_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $sprint_status_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_artifact_empty_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $release_artifact_open_id  = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_closed_id = $tracker_builder->buildArtifact($sprint_tracker->getId());

        $tracker_builder->buildLastChangeset($release_artifact_empty_id);
        $release_artifact_open_changeset  = $tracker_builder->buildLastChangeset($release_artifact_open_id);
        $sprint_artifact_closed_changeset = $tracker_builder->buildLastChangeset($sprint_artifact_closed_id);

        $this->expected_results = [
            $release_artifact_empty_id => [],
            $release_artifact_open_id  => [new StaticListValueRepresentation('Open', null)],
            $sprint_artifact_closed_id => [
                new StaticListValueRepresentation('Closed', null),
                new StaticListValueRepresentation('Also closed', null),
            ],
        ];
        $tracker_builder->buildListValue($release_artifact_open_changeset, $release_status_field_id, $release_status_values['open'][0]);
        $tracker_builder->buildListValue($sprint_artifact_closed_changeset, $sprint_status_field_id, $sprint_status_values['closed'][0]);
        $tracker_builder->buildListValue($sprint_artifact_closed_changeset, $sprint_status_field_id, $sprint_status_values['closed'][1]);
    }

    public function testItReturnsColumns(): void
    {
        $result = $this->getQueryResults(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @status FROM @project = 'self' WHERE field_status = '' OR field_status != ''",
                )->build(),
            $this->user,
        );
        self::assertSame(3, $result->getTotalSize());
        self::assertCount(2, $result->selected);
        self::assertSame('@status', $result->selected[1]->name);
        self::assertSame('list_static', $result->selected[1]->type);
        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('@status', $artifact);
            $value = $artifact['@status'];
            self::assertInstanceOf(StaticListRepresentation::class, $value);
            $values[] = $value->value;
        }
        self::assertEqualsCanonicalizing(
            array_values($this->expected_results),
            $values
        );
    }
}
