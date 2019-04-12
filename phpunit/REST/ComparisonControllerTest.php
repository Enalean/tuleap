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

require_once __DIR__ . '/../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tuleap\Baseline\BaselineRepository;
use Tuleap\Baseline\ComparisonService;
use Tuleap\Baseline\CurrentUserProvider;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\Baseline\Support\CurrentUserContext;

class ComparisonControllerTest extends TestCase
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

    /** @before */
    protected function createInstance()
    {
        $this->comparison_service    = Mockery::mock(ComparisonService::class);
        $this->current_user_provider = Mockery::mock(CurrentUserProvider::class);
        $this->current_user_provider->allows(['getUser' => $this->current_user]);
        $this->baseline_repository = Mockery::mock(BaselineRepository::class);

        $this->controller = new ComparisonController(
            $this->comparison_service,
            $this->current_user_provider,
            $this->baseline_repository
        );
    }

    public function testGetByIdThrowsNotFoundRestExceptionWhenComparisonNotFound()
    {
        $this->expectException(NotFoundRestException::class);
        $this->comparison_service->allows(['findById' => null]);
        $this->controller->getById(1);
    }
}
