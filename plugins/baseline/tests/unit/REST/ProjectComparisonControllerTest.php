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

use Tuleap\Baseline\Domain\ComparisonService;
use Tuleap\Baseline\Domain\ComparisonsPage;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Factory\ComparisonFactory;
use Tuleap\Baseline\Factory\ProjectFactory;
use Tuleap\Baseline\Domain\ProjectRepository;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\Baseline\Support\CurrentUserContext;

final class ProjectComparisonControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use CurrentUserContext;

    private ProjectComparisonController $controller;

    /** @var CurrentUserProvider&\PHPUnit\Framework\MockObject\MockObject */
    private $current_user_provider;

    /** @var ComparisonService&\PHPUnit\Framework\MockObject\MockObject */
    private $comparison_service;

    /** @var ProjectRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $project_repository;

    private ProjectIdentifier $a_project;

    /**
     * @before
     */
    public function createInstance(): void
    {
        $this->current_user_provider = $this->createMock(CurrentUserProvider::class);
        $this->current_user_provider->method('getUser')->willReturn($this->current_user);

        $this->comparison_service = $this->createMock(ComparisonService::class);
        $this->project_repository = $this->createMock(ProjectRepository::class);

        $this->controller = new ProjectComparisonController(
            $this->current_user_provider,
            $this->comparison_service,
            $this->project_repository
        );
    }

    /** @before */
    public function createEntities(): void
    {
        $this->a_project = ProjectFactory::one();
    }

    public function testGet(): void
    {
        $this->project_repository
            ->method('findById')
            ->with($this->current_user, 102)
            ->willReturn($this->a_project);

        $this->comparison_service
            ->method('findByProject')
            ->with($this->current_user, $this->a_project, 10, 7)
            ->willReturn(
                new ComparisonsPage(
                    [ComparisonFactory::one()],
                    10,
                    7,
                    233
                )
            );

        $representation = $this->controller->get(102, 10, 7);

        self::assertEquals(1, count($representation->comparisons));
        self::assertEquals(233, $representation->total_count);
    }

    public function testGetThrows404WhenNoProjectFound(): void
    {
        $this->expectException(NotFoundRestException::class);

        $this->project_repository
            ->method('findById')
            ->with($this->current_user, 102)
            ->willReturn(null);

        $this->controller->get(102, 10, 0);
    }
}
