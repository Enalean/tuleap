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

use Tuleap\Baseline\Adapter\UserProxy;
use Tuleap\Baseline\Domain\BaselineRepository;
use Tuleap\Baseline\Domain\ComparisonRepository;
use Tuleap\Baseline\Domain\ComparisonService;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Factory\ComparisonFactory;
use Tuleap\Baseline\Domain\NotAuthorizedException;
use Tuleap\Baseline\REST\Exception\ForbiddenRestException;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\Test\Builders\UserTestBuilder;

final class ComparisonControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ComparisonController $controller;

    /** @var ComparisonService&\PHPUnit\Framework\MockObject\MockObject */
    private $comparison_service;

    /** @var CurrentUserProvider&\PHPUnit\Framework\MockObject\MockObject */
    private $current_user_provider;

    /** @var BaselineRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $baseline_repository;

    /** @var ComparisonRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $comparison_repository;
    private UserProxy $current_user;

    /** @before */
    protected function createInstance(): void
    {
        $this->current_user = UserProxy::fromUser(UserTestBuilder::aUser()->build());

        $this->comparison_service    = $this->createMock(ComparisonService::class);
        $this->current_user_provider = $this->createMock(CurrentUserProvider::class);
        $this->current_user_provider->method('getUser')->willReturn($this->current_user);
        $this->baseline_repository   = $this->createMock(BaselineRepository::class);
        $this->comparison_repository = $this->createMock(ComparisonRepository::class);

        $this->controller = new ComparisonController(
            $this->comparison_service,
            $this->current_user_provider,
            $this->baseline_repository,
            $this->comparison_repository
        );
    }

    public function testGetByIdThrowsNotFoundRestExceptionWhenComparisonNotFound(): void
    {
        $this->expectException(NotFoundRestException::class);

        $this->comparison_service->method('findById')->willReturn(null);
        $this->controller->getById(1);
    }

    public function testDelete(): void
    {
        $comparison = ComparisonFactory::withId(2);

        $this->comparison_repository
            ->method('findById')
            ->willReturn($comparison);

        $this->comparison_service
            ->expects(self::atLeast(1))
            ->method('delete')
            ->with($this->current_user, $comparison);

        $this->controller->delete(2);
    }

    public function testDeleteThrows404WhenComparisonNotFound(): void
    {
        $this->expectException(NotFoundRestException::class);

        $this->comparison_repository
            ->method('findById')
            ->willReturn(null);

        $this->controller->delete(2);
    }

    public function testDeleteThrows403WhenNotAllowed(): void
    {
        $this->expectException(ForbiddenRestException::class);

        $this->comparison_repository
            ->method('findById')
            ->willReturn(ComparisonFactory::one());

        $this->comparison_service
            ->method('delete')
            ->willThrowException(new NotAuthorizedException('not allowed'));

        $this->controller->delete(2);
    }
}
