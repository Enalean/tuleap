<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\REST;

require_once __DIR__ . '/IntegrationTestCaseWithStubs.php';

use DateTime;
use Mockery;
use PFUser;
use Project;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Factory\MilestoneFactory;
use Tuleap\Baseline\Factory\TrackerFactory;
use Tuleap\Baseline\Support\DateTimeFactory;
use Tuleap\GlobalLanguageMock;

class ProjectBaselineControllerIntTest extends IntegrationTestCaseWithStubs
{
    use GlobalLanguageMock;

    /** @var ProjectBaselineController */
    private $controller;

    /** @before */
    public function getTestedComponent()
    {
        $this->controller = $this->getContainer()->get(ProjectBaselineController::class);
    }

    public function testGet()
    {
        $this->permissions->permitAll();

        $project = Mockery::mock(Project::class)
            ->shouldReceive('getID')
            ->andReturn(102)
            ->getMock();
        $this->project_repository->add($project);

        $this->baseline_repository->addBaseline(
            BaselineFactory::one()
                ->id(3)
                ->name('Matching baseline')
                ->milestone(
                    MilestoneFactory::one()
                        ->id(13)
                        ->tracker(
                            TrackerFactory::one()
                                ->project($project)
                                ->build()
                        )
                        ->build()
                )
                ->snapshotDate(DateTime::createFromFormat('Y-m-d H:i:s', '2019-03-21 14:47:03'))
                ->author(new PFuser(['user_id' => 22]))
                ->build()
        );

        $paginated_baselines = $this->controller->get(102, 10, 0);

        $expected_representation = new BaselineRepresentation(
            3,
            'Matching baseline',
            13,
            '2019-03-21T14:47:03+01:00',
            22
        );
        $this->assertEquals(1, $paginated_baselines->total_count);
        $this->assertEquals([$expected_representation], $paginated_baselines->baselines);
    }

    public function testGetReturnsPaginatedBaselines()
    {
        $this->permissions->permitAll();

        $project = Mockery::mock(Project::class)
            ->shouldReceive('getID')
            ->andReturn(102)
            ->getMock();
        $this->project_repository->add($project);

        $snapshot_date = DateTimeFactory::one();
        for ($id = 1; $id < 10; $id++) {
            $snapshot_date->modify('+1 day');
            $baseline = BaselineFactory::fromProject($project)
                ->id($id)
                ->snapshotDate($snapshot_date)
                ->build();
            $this->baseline_repository->addBaseline($baseline);
        }
        $paginated_baselines = $this->controller->get(102, 3, 4);

        $this->assertEquals(9, $paginated_baselines->total_count);
        $ids = $this->extractBaselineIds($paginated_baselines);
        $this->assertEquals([5, 6, 7], $ids);
    }

    /**
     * @return int[]
     */
    private function extractBaselineIds(BaselinesPageRepresentation $paginated_baselines): array
    {
        return array_map(
            function (BaselineRepresentation $baseline) {
                return $baseline->id;
            },
            $paginated_baselines->baselines
        );
    }
}
