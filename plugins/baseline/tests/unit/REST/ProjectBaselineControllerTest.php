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

use Tuleap\Baseline\Adapter\UserProxy;
use Tuleap\Baseline\Domain\BaselineService;
use Tuleap\Baseline\Domain\BaselinesPage;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Factory\ProjectFactory;
use Tuleap\Baseline\Domain\NotAuthorizedException;
use Tuleap\Baseline\Domain\ProjectRepository;
use Tuleap\Baseline\REST\Exception\ForbiddenRestException;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectBaselineControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProjectBaselineController $controller;

    /** @var CurrentUserProvider&\PHPUnit\Framework\MockObject\MockObject */
    private $current_user_provider;

    /** @var BaselineService&\PHPUnit\Framework\MockObject\MockObject */
    private $baseline_service;

    private ProjectRepository&\PHPUnit\Framework\MockObject\MockObject $project_repository;
    private ProjectIdentifier $a_project;
    private UserProxy $current_user;

    /**
     * @before
     */
    public function createInstance(): void
    {
        $this->current_user = UserProxy::fromUser(UserTestBuilder::aUser()->build());

        $this->current_user_provider = $this->createMock(CurrentUserProvider::class);
        $this->current_user_provider->method('getUser')->willReturn($this->current_user);

        $this->baseline_service   = $this->createMock(BaselineService::class);
        $this->project_repository = $this->createMock(ProjectRepository::class);

        $this->controller = new ProjectBaselineController(
            $this->current_user_provider,
            $this->baseline_service,
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

        $this->baseline_service
            ->method('findByProject')
            ->with($this->current_user, $this->a_project, 10, 7)
            ->willReturn(
                new BaselinesPage(
                    [BaselineFactory::one()->build()],
                    10,
                    7,
                    233
                )
            );

        $representation = $this->controller->get(102, 10, 7);

        self::assertEquals(1, count($representation->baselines));
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

    public function testGetThrows403WhenNotAuthorized(): void
    {
        $this->expectException(ForbiddenRestException::class);

        $this->project_repository
            ->method('findById')
            ->willReturn($this->a_project);

        $this->baseline_service
            ->method('findByProject')
            ->willThrowException(new NotAuthorizedException('not authorized'));

        $this->controller->get(102, 10, 0);
        self::assertTrue(false);
    }
}
