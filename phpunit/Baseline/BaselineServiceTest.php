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

namespace Tuleap\Baseline;

require_once __DIR__ . '/../bootstrap.php';

use DateTime;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Support\DateTimeFactory;
use Tuleap\GlobalLanguageMock;

class BaselineServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /** @var BaselineService */
    private $service;

    /** @var BaselineRepository|MockInterface */
    private $baseline_repository;

    /** @var CurrentUserProvider|MockInterface */
    private $current_user_provider;

    /** @var Clock|MockInterface */
    private $clock;

    /** @before */
    public function createInstance()
    {
        $this->baseline_repository   = Mockery::mock(BaselineRepository::class);
        $this->current_user_provider = Mockery::mock(CurrentUserProvider::class);
        $this->clock                 = Mockery::mock(Clock::class);

        $this->service = new BaselineService(
            $this->baseline_repository,
            $this->current_user_provider,
            $this->clock
        );
    }

    /** @var PFUser */
    private $a_user;

    /** @var Project|MockInterface */
    private $a_project;

    /** @var DateTime */
    private $a_date;

    /** @before */
    public function createEntities()
    {
        $this->a_user    = new PFUser();
        $this->a_project = Mockery::mock(Project::class);
        $this->a_date    = DateTimeFactory::one();
    }

    public function testFinByProject()
    {
        $baselines = [BaselineFactory::one()->build()];
        $this->baseline_repository
            ->shouldReceive('findByProject')
            ->with($this->a_user, $this->a_project, 10, 3)
            ->andReturn($baselines);
        $this->baseline_repository
            ->shouldReceive('countByProject')
            ->with($this->a_project)
            ->andReturn(233);

        $baselines_page = $this->service->findByProject($this->a_user, $this->a_project, 10, 3);

        $this->assertEquals($baselines, $baselines_page->getBaselines());
        $this->assertEquals(233, $baselines_page->getTotalBaselineCount());
        $this->assertEquals(10, $baselines_page->getPageSize());
        $this->assertEquals(3, $baselines_page->getBaselineOffset());
    }
}
