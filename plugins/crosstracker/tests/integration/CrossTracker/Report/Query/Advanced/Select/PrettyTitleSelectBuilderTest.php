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

use ProjectUGroup;
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

final class PrettyTitleSelectBuilderTest extends CrossTrackerFieldTestCase
{
    /**
     * @var list<int>
     */
    private array $artifact_ids;
    /**
     * @var array<int, array>
     */
    private array $expected_values;

    public function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $user       = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $user->getId(), $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');

        $release_text_field_id = $tracker_builder->buildTextField(
            $release_tracker->getId(),
            'text_field',
        );
        $tracker_builder->buildTitleSemantic($release_tracker->getId(), $release_text_field_id);
        $sprint_text_field_id = $tracker_builder->buildTextField(
            $sprint_tracker->getId(),
            'text_field',
        );
        $tracker_builder->buildTitleSemantic($sprint_tracker->getId(), $sprint_text_field_id);

        $tracker_builder->setReadPermission(
            $release_text_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $sprint_text_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_artifact_with_text_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_with_text_id  = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->artifact_ids            = [$release_artifact_with_text_id, $sprint_artifact_with_text_id];

        $release_artifact_with_text_changeset = $tracker_builder->buildLastChangeset($release_artifact_with_text_id);
        $sprint_artifact_with_text_changeset  = $tracker_builder->buildLastChangeset($sprint_artifact_with_text_id);

        $this->expected_values = [
            $release_artifact_with_text_id => [
                '@pretty_title.tracker' => 'Release',
                '@pretty_title.color'   => 'inca-silver',
                '@pretty_title'         => 'Hello World!',
                '@pretty_title.format'  => 'text',
            ],
            $sprint_artifact_with_text_id  => [
                '@pretty_title.tracker' => 'Sprint',
                '@pretty_title.color'   => 'inca-silver',
                '@pretty_title'         => '**Title**',
                '@pretty_title.format'  => 'commonmark',
            ],
        ];
        $tracker_builder->buildTextValue(
            $release_artifact_with_text_changeset,
            $release_text_field_id,
            'Hello World!',
            'text'
        );
        $tracker_builder->buildTextValue(
            $sprint_artifact_with_text_changeset,
            $sprint_text_field_id,
            '**Title**',
            'commonmark'
        );
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
            $builder->getSelectFrom(new Metadata('pretty_title')),
            $this->artifact_ids,
        );

        self::assertCount(2, $results);
        foreach ($results as $result) {
            self::assertArrayHasKey('id', $result);
            $id = $result['id'];
            self::assertArrayHasKey('@pretty_title.tracker', $result);
            self::assertArrayHasKey('@pretty_title.color', $result);
            self::assertArrayHasKey('@pretty_title', $result);
            self::assertArrayHasKey('@pretty_title.format', $result);
            self::assertSame($this->expected_values[$id]['@pretty_title.tracker'], $result['@pretty_title.tracker']);
            self::assertSame($this->expected_values[$id]['@pretty_title.color'], $result['@pretty_title.color']);
            self::assertSame($this->expected_values[$id]['@pretty_title'], $result['@pretty_title']);
            self::assertSame($this->expected_values[$id]['@pretty_title.format'], $result['@pretty_title.format']);
        }
    }
}
