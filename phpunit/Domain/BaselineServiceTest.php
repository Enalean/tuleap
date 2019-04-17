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

use DateTimeInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Factory\ProjectFactory;
use Tuleap\Baseline\Stub\FrozenClock;
use Tuleap\Baseline\Support\CurrentUserContext;
use Tuleap\Baseline\Support\DateTimeFactory;

class BaselineServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use CurrentUserContext;

    /** @var BaselineService */
    private $service;

    /** @var BaselineRepository|MockInterface */
    private $baseline_repository;

    /** @var FrozenClock */
    private $clock;

    /** @var BaselineAuthorizations */
    private $authorizations;

    /** @before */
    public function createInstance()
    {
        $this->baseline_repository = Mockery::mock(BaselineRepository::class);
        $this->clock               = new FrozenClock();
        $this->authorizations      = Mockery::mock(BaselineAuthorizationsImpl::class);

        $this->service = new BaselineService(
            $this->baseline_repository,
            $this->clock,
            $this->authorizations
        );
    }

    /** @var Project|MockInterface */
    private $a_project;

    /** @var DateTimeInterface */
    private $a_date;

    /** @before */
    public function createEntities()
    {
        $this->a_project = ProjectFactory::one();
        $this->a_date    = DateTimeFactory::one();
    }

    public function testCreateAddsGivenBaseline()
    {
        $this->authorizations->allows(['canCreateBaseline' => true]);

        $baseline = $this->buildATransientBaseline();
        $this->baseline_repository
            ->shouldReceive('add')
            ->with($baseline, $this->current_user, $this->clock->now());

        $this->service->create($this->current_user, $baseline);
    }

    public function testCreateThrowsNotAuthorizedExceptionWhenNotAuthorized()
    {
        $this->expectException(NotAuthorizedException::class);

        $baseline = $this->buildATransientBaseline();
        $this->authorizations->allows()
            ->canCreateBaseline($this->current_user, $baseline)
            ->andReturn(false);

        $this->service->create($this->current_user, $baseline);
    }

    public function testDeleteDeletesGivenBaseline()
    {
        $this->authorizations->allows(['canDeleteBaseline' => true]);

        $baseline = BaselineFactory::one()->build();
        $this->baseline_repository
            ->shouldReceive('delete')
            ->with($baseline, $this->current_user);

        $this->service->delete($this->current_user, $baseline);
    }

    public function testDeleteThrowsNotAuthorizedExceptionWhenNotAuthorized()
    {
        $this->expectException(NotAuthorizedException::class);

        $baseline = BaselineFactory::one()->build();
        $this->authorizations->allows()
            ->canDeleteBaseline($this->current_user, $baseline)
            ->andReturn(false);

        $this->service->delete($this->current_user, $baseline);
    }

    public function testFindByProject()
    {
        $this->authorizations->allows(['canReadBaselinesOnProject' => true]);

        $baselines = [BaselineFactory::one()->build()];
        $this->baseline_repository
            ->shouldReceive('findByProject')
            ->with($this->current_user, $this->a_project, 10, 3)
            ->andReturn($baselines);
        $this->baseline_repository
            ->shouldReceive('countByProject')
            ->with($this->a_project)
            ->andReturn(233);

        $baselines_page = $this->service->findByProject($this->current_user, $this->a_project, 10, 3);

        $this->assertEquals($baselines, $baselines_page->getBaselines());
        $this->assertEquals(233, $baselines_page->getTotalBaselineCount());
        $this->assertEquals(10, $baselines_page->getPageSize());
        $this->assertEquals(3, $baselines_page->getBaselineOffset());
    }

    public function testFindByProjectThrowsNotAuthorizedExceptionWhenNotAuthorized()
    {
        $this->expectException(NotAuthorizedException::class);

        $project = ProjectFactory::one();
        $this->authorizations->allows()
            ->canReadBaselinesOnProject($this->current_user, $project)
            ->andReturn(false);

        $this->service->findByProject($this->current_user, $project, 10, 0);
    }

    private function buildATransientBaseline(): TransientBaseline
    {
        return new TransientBaseline('baseline name', BaselineArtifactFactory::one()->build());
    }
}
