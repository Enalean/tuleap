<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\Query\Advanced\Select;

use PFUser;
use ProjectUGroup;
use Tuleap\CrossTracker\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\NumericResultRepresentation;
use Tuleap\CrossTracker\Tests\CrossTrackerQueryTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NumericSelectBuilderTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    /**
     * @var array<int, int|float|null>
     */
    private array $expected_values;
    private PFUser $user;

    #[\Override]
    public function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $this->user = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_id);
        $this->uuid = $this->addWidgetToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $release_int_field_id  = $tracker_builder->buildIntField(
            $release_tracker->getId(),
            'numeric_field',
        );
        $sprint_float_field_id = $tracker_builder->buildFloatField(
            $sprint_tracker->getId(),
            'numeric_field',
        );

        $tracker_builder->grantReadPermissionOnField(
            $release_int_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $sprint_float_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_artifact_empty_id     = $tracker_builder->buildArtifact($release_tracker->getId());
        $release_artifact_with_int_id  = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_with_float_id = $tracker_builder->buildArtifact($sprint_tracker->getId());

        $tracker_builder->buildLastChangeset($release_artifact_empty_id);
        $release_artifact_with_int_changeset  = $tracker_builder->buildLastChangeset($release_artifact_with_int_id);
        $sprint_artifact_with_float_changeset = $tracker_builder->buildLastChangeset($sprint_artifact_with_float_id);

        $this->expected_values = [
            $release_artifact_empty_id     => null,
            $release_artifact_with_int_id  => 42,
            $sprint_artifact_with_float_id => 3.14,
        ];
        $tracker_builder->buildIntValue(
            $release_artifact_with_int_changeset,
            $release_int_field_id,
            (int) $this->expected_values[$release_artifact_with_int_id],
        );

        $tracker_builder->buildFloatValue(
            $sprint_artifact_with_float_changeset,
            $sprint_float_field_id,
            (float) $this->expected_values[$sprint_artifact_with_float_id],
        );
    }

    public function testItReturnsColumns(): void
    {
        $result = $this->getQueryResults(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT numeric_field FROM @project = 'self' WHERE numeric_field = '' OR numeric_field != ''",
                )->build(),
            $this->user,
        );

        self::assertSame(3, $result->getTotalSize());
        self::assertCount(2, $result->selected);
        self::assertSame('numeric_field', $result->selected[1]->name);
        self::assertSame('numeric', $result->selected[1]->type);
        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('numeric_field', $artifact);
            $value = $artifact['numeric_field'];
            self::assertInstanceOf(NumericResultRepresentation::class, $value);
            $values[] = $value->value;
        }
        self::assertEqualsCanonicalizing(array_values($this->expected_values), $values);
    }
}
