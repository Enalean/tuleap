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

use Tuleap\Baseline\Factory\ProjectFactory;
use Tuleap\Baseline\Factory\TransientComparisonFactory;
use Tuleap\REST\JsonCast;

class ProjectComparisonControllerIntTest extends IntegrationTestCaseWithStubs
{
    /** @var ProjectComparisonController */
    private $controller;

    /** @before */
    public function getTestedComponent(): void
    {
        $this->controller = $this->getContainer()->get(ProjectComparisonController::class);
    }

    public function testGet(): void
    {
        $project = ProjectFactory::oneWithId(102);
        $this->project_repository->add($project);

        $comparison = $this->comparison_repository->add(
            TransientComparisonFactory::fromProject($project)->build(),
            $this->current_user
        );

        $paginated_baselines = $this->controller->get(102, 10, 0);

        $formatted_date = JsonCast::fromDateTimeToDate($comparison->getCreationDate());
        self::assertTrue(is_string($formatted_date));

        $expected_representation = new ComparisonRepresentation(
            $comparison->getId(),
            $comparison->getName(),
            $comparison->getComment(),
            $comparison->getBaseBaseline()->getId(),
            $comparison->getComparedToBaseline()->getId(),
            $comparison->getAuthor()->getId(),
            $formatted_date,
        );
        self::assertEquals(1, $paginated_baselines->total_count);
        self::assertEquals([$expected_representation], $paginated_baselines->comparisons);
    }

    public function testGetReturnsPaginatedBaselines(): void
    {
        $project = ProjectFactory::oneWithId(102);
        $this->project_repository->add($project);

        for ($id = 1; $id < 10; $id++) {
            $this->comparison_repository->add(
                TransientComparisonFactory::fromProject($project)->build(),
                $this->current_user
            );
        }
        $paginated_baselines = $this->controller->get(102, 3, 4);

        self::assertEquals(9, $paginated_baselines->total_count);
        $ids = $this->extractComparisonIds($paginated_baselines);
        self::assertEquals([5, 6, 7], $ids);
    }

    /**
     * @return int[]
     */
    private function extractComparisonIds(ComparisonsPageRepresentation $paginated_comparisons): array
    {
        return array_map(
            function (ComparisonRepresentation $baseline) {
                return $baseline->id;
            },
            $paginated_comparisons->comparisons
        );
    }
}
