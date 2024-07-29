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

use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\CrossTrackerExpertQueryReportDao;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\MetadataSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\AssignedTo\AssignedToSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\Description\DescriptionSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\Status\StatusSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\Title\TitleSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Special\PrettyTitle\PrettyTitleSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Special\ProjectName\ProjectNameSelectFromBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class ProjectNameSelectBuilderTest extends CrossTrackerFieldTestCase
{
    /**
     * @var array<int, array>
     */
    private array $expected_values;
    /**
     * @var list<int>
     */
    private array $artifact_ids;

    public function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project_1    = $core_builder->buildProject('My project');
        $project_1_id = (int) $project_1->getId();
        $project_2    = $core_builder->buildProject('Another project', '"\u2694\ufe0f"');
        $project_2_id = (int) $project_2->getId();
        $user         = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $user->getId(), $project_1_id);
        $core_builder->addUserToProjectMembers((int) $user->getId(), $project_2_id);

        $release_tracker = $tracker_builder->buildTracker($project_1_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_2_id, 'Sprint');

        $release_artifact_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_id  = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->artifact_ids  = [$release_artifact_id, $sprint_artifact_id];
        $tracker_builder->buildLastChangeset($release_artifact_id);
        $tracker_builder->buildLastChangeset($sprint_artifact_id);

        $this->expected_values = [
            $release_artifact_id => ['@project.name' => 'My project', '@project.icon' => ''],
            $sprint_artifact_id  => ['@project.name' => 'Another project', '@project.icon' => '"\u2694\ufe0f"'],
        ];
    }

    public function testItReturnsColumns(): void
    {
        $dao     = new CrossTrackerExpertQueryReportDao();
        $builder = new MetadataSelectFromBuilder(
            new TitleSelectFromBuilder(),
            new DescriptionSelectFromBuilder(),
            new StatusSelectFromBuilder(),
            new AssignedToSelectFromBuilder(),
            new ProjectNameSelectFromBuilder(),
            new PrettyTitleSelectFromBuilder(),
        );
        $results = $dao->searchArtifactsColumnsMatchingIds(
            $builder->getSelectFrom(new Metadata('project.name')),
            $this->artifact_ids,
        );

        self::assertCount(2, $results);
        foreach ($results as $result) {
            self::assertArrayHasKey('id', $result);
            $id = $result['id'];
            self::assertArrayHasKey('@project.name', $result);
            self::assertArrayHasKey('@project.icon', $result);
            self::assertSame($this->expected_values[$id]['@project.name'], $result['@project.name']);
            self::assertSame($this->expected_values[$id]['@project.icon'], $result['@project.icon']);
        }
    }
}
