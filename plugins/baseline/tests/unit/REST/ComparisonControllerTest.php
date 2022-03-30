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
use Tuleap\Baseline\Domain\BaselineRepository;
use Tuleap\Baseline\Domain\ComparisonRepository;
use Tuleap\Baseline\Domain\ComparisonService;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Factory\ComparisonFactory;
use Tuleap\Baseline\Domain\NotAuthorizedException;
use Tuleap\Baseline\REST\Exception\ForbiddenRestException;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\Baseline\Support\CurrentUserContext;

class ComparisonControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use CurrentUserContext;

    /** @var ComparisonController */
    private $controller;

    /** @var ComparisonService|MockInterface */
    private $comparison_service;

    /** @var CurrentUserProvider|MockInterface */
    private $current_user_provider;

    /** @var BaselineRepository|MockInterface */
    private $baseline_repository;

    /** @var ComparisonRepository|MockInterface */
    private $comparison_repository;

    /** @before */
    protected function createInstance()
    {
        $this->comparison_service    = Mockery::mock(ComparisonService::class);
        $this->current_user_provider = Mockery::mock(CurrentUserProvider::class);
        $this->current_user_provider->allows(['getUser' => $this->current_user]);
        $this->baseline_repository   = Mockery::mock(BaselineRepository::class);
        $this->comparison_repository = Mockery::mock(ComparisonRepository::class);

        $this->controller = new ComparisonController(
            $this->comparison_service,
            $this->current_user_provider,
            $this->baseline_repository,
            $this->comparison_repository
        );
    }

    public function testGetByIdThrowsNotFoundRestExceptionWhenComparisonNotFound()
    {
        $this->expectException(NotFoundRestException::class);
        $this->comparison_service->allows(['findById' => null]);
        $this->controller->getById(1);
    }

    public function testDelete()
    {
        $comparison = ComparisonFactory::withId(2);

        $this->comparison_repository
            ->shouldReceive('findById')
            ->andReturn($comparison);

        $this->comparison_service
            ->shouldReceive('delete')
            ->with($this->current_user, $comparison);

        $this->controller->delete(2);
    }

    public function testDeleteThrows404WhenComparisonNotFound()
    {
        $this->expectException(NotFoundRestException::class);

        $this->comparison_repository
            ->shouldReceive('findById')
            ->andReturn(null);

        $this->controller->delete(2);
    }

    public function testDeleteThrows403WhenNotAllowed()
    {
        $this->expectException(ForbiddenRestException::class);

        $this->comparison_repository
            ->shouldReceive('findById')
            ->andReturn(ComparisonFactory::one());

        $this->comparison_service
            ->shouldReceive('delete')
            ->andThrow(new NotAuthorizedException('not allowed'));

        $this->controller->delete(2);
    }
}
