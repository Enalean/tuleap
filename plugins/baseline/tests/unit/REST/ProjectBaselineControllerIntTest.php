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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\REST;

require_once __DIR__ . '/IntegrationTestCaseWithStubs.php';

use DateTimeImmutable;
use PFUser;
use Tuleap\Baseline\Adapter\UserProxy;
use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Factory\ProjectFactory;
use Tuleap\Baseline\Support\DateTimeFactory;
use Tuleap\GlobalLanguageMock;

class ProjectBaselineControllerIntTest extends IntegrationTestCaseWithStubs
{
    use GlobalLanguageMock;

    /** @var ProjectBaselineController */
    private $controller;

    /** @before */
    public function getTestedComponent(): void
    {
        $this->controller = $this->getContainer()->get(ProjectBaselineController::class);
    }

    public function testGet(): void
    {
        $project = ProjectFactory::oneWithId(102);
        $this->project_repository->add($project);

        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-03-21 14:47:03');
        self::assertInstanceOf(DateTimeImmutable::class, $date);

        $this->baseline_repository->addBaseline(
            BaselineFactory::one()
                ->id(3)
                ->name('Matching baseline')
                ->artifact(
                    BaselineArtifactFactory::one()
                        ->id(13)
                        ->project($project)
                        ->build()
                )
                ->snapshotDate($date)
                ->author(UserProxy::fromUser(new PFUser(['user_id' => 22])))
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
        self::assertEquals(1, $paginated_baselines->total_count);
        self::assertEquals([$expected_representation], $paginated_baselines->baselines);
    }

    public function testGetReturnsPaginatedBaselines(): void
    {
        $project = ProjectFactory::oneWithId(102);
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

        self::assertEquals(9, $paginated_baselines->total_count);
        $ids = $this->extractBaselineIds($paginated_baselines);
        self::assertEquals([5, 6, 7], $ids);
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
