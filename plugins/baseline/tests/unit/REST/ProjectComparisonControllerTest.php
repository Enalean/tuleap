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

require_once __DIR__ . '/../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Project;
use Tuleap\Baseline\Domain\ComparisonService;
use Tuleap\Baseline\Domain\ComparisonsPage;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Factory\ComparisonFactory;
use Tuleap\Baseline\Factory\ProjectFactory;
use Tuleap\Baseline\Domain\ProjectRepository;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\Baseline\Support\CurrentUserContext;

class ProjectComparisonControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use CurrentUserContext;

    /** @var ProjectComparisonController */
    private $controller;

    /** @var CurrentUserProvider|MockInterface */
    private $current_user_provider;

    /** @var ComparisonService|MockInterface */
    private $comparison_service;

    /** @var ProjectRepository */
    private $project_repository;

    /** @var Project|MockInterface */
    private $a_project;

    /**
     * @before
     */
    public function createInstance()
    {
        $this->current_user_provider = Mockery::mock(CurrentUserProvider::class)->shouldIgnoreMissing();
        $this->current_user_provider
            ->allows(['getUser' => $this->current_user])
            ->byDefault();
        $this->comparison_service = Mockery::mock(ComparisonService::class);
        $this->project_repository = Mockery::mock(ProjectRepository::class);

        $this->controller = new ProjectComparisonController(
            $this->current_user_provider,
            $this->comparison_service,
            $this->project_repository
        );
    }

    /** @before */
    public function createEntities()
    {
        $this->a_project = ProjectFactory::one();
    }

    public function testGet()
    {
        $this->project_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 102)
            ->andReturn($this->a_project);

        $this->comparison_service
            ->shouldReceive('findByProject')
            ->with($this->current_user, $this->a_project, 10, 7)
            ->andReturn(
                new ComparisonsPage(
                    [ComparisonFactory::one()],
                    10,
                    7,
                    233
                )
            );

        $representation = $this->controller->get(102, 10, 7);

        $this->assertEquals(1, count($representation->comparisons));
        $this->assertEquals(233, $representation->total_count);
    }

    public function testGetThrows404WhenNoProjectFound()
    {
        $this->expectException(NotFoundRestException::class);

        $this->project_repository
            ->shouldReceive('findById')
            ->with($this->current_user, 102)
            ->andReturn(null);

        $this->controller->get(102, 10, 0);
    }
}
