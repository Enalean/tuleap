<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column;

use Cardwall_Column;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Cardwall\OnTop\Config\ColumnCollection;
use Tuleap\Cardwall\OnTop\Config\ColumnFactory;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\TrackerMappingPresenterBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ColumnPresenterCollectionRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ColumnFactory&MockObject $column_factory;
    private TrackerMappingPresenterBuilder&MockObject $mappings_builder;
    private \Tracker $release_tracker;
    private \Planning_ArtifactMilestone $milestone;

    protected function setUp(): void
    {
        $this->column_factory   = $this->createMock(ColumnFactory::class);
        $this->mappings_builder = $this->createMock(TrackerMappingPresenterBuilder::class);

        $this->release_tracker = TrackerTestBuilder::aTracker()->withId(290)->build();

        $project_id      = 110;
        $this->milestone = new \Planning_ArtifactMilestone(
            ProjectTestBuilder::aProject()->withId($project_id)->build(),
            PlanningBuilder::aPlanning($project_id)->withMilestoneTracker($this->release_tracker)->build(),
            ArtifactTestBuilder::anArtifact(42)->build(),
        );
    }

    private function getPresenters(\PFUser $user): array
    {
        $retriever = new ColumnPresenterCollectionRetriever(
            $this->column_factory,
            $this->mappings_builder
        );
        return $retriever->getColumns($user, $this->milestone);
    }

    public function testEmptyCollection(): void
    {
        $this->column_factory->expects(self::once())
            ->method('getDashboardColumns')
            ->with($this->release_tracker)
            ->willReturn(new ColumnCollection());

        $user = UserTestBuilder::aUser()->build();

        self::assertEmpty($this->getPresenters($user));
    }

    public function testCollection(): void
    {
        $todo_column    = new Cardwall_Column(2, 'To do', 'fiesta-red');
        $ongoing_column = new Cardwall_Column(4, 'On going', '');
        $done_column    = new Cardwall_Column(6, 'Done', 'rgb(135,219,239)');
        $this->column_factory->expects(self::once())
            ->method('getDashboardColumns')
            ->with($this->release_tracker)
            ->willReturn(new ColumnCollection([$todo_column, $ongoing_column, $done_column]));

        $user = $this->createMock(\PFUser::class);
        $user->method('getPreference')
            ->willReturnMap([
                ['plugin_taskboard_collapse_column_42_2', false],
                ['plugin_taskboard_collapse_column_42_4', false],
                ['plugin_taskboard_collapse_column_42_6', '1'],
            ]);

        $this->mappings_builder->method('buildMappings')->with($this->milestone, self::callback(
            static function (Cardwall_Column $column): bool {
                $column_id = $column->getId();
                return $column_id === 2 || $column_id === 4 || $column_id === 6;
            }
        ))->willReturn([]);

        $collection = $this->getPresenters($user);

        self::assertCount(3, $collection);
        self::assertFalse($collection[0]->is_collapsed);
        self::assertFalse($collection[1]->is_collapsed);
        self::assertTrue($collection[2]->is_collapsed);
    }
}
