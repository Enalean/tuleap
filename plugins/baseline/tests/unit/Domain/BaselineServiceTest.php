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

namespace Tuleap\Baseline\Domain;

require_once __DIR__ . '/../bootstrap.php';

use Tuleap\Baseline\Adapter\AuthorizationsImpl;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Factory\ProjectFactory;
use Tuleap\Baseline\Factory\TransientBaselineFactory;
use Tuleap\Baseline\Stub\FrozenClock;
use Tuleap\Baseline\Support\CurrentUserContext;
use Tuleap\Baseline\Support\DateTimeFactory;

final class BaselineServiceTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use CurrentUserContext;

    private BaselineService $service;

    /** @var BaselineRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $baseline_repository;

    /** @var ComparisonRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $comparison_repository;

    private FrozenClock $clock;

    /** @var Authorizations&\PHPUnit\Framework\MockObject\MockObject */
    private $authorizations;

    /** @before */
    public function createInstance(): void
    {
        $this->baseline_repository   = $this->createMock(BaselineRepository::class);
        $this->comparison_repository = $this->createMock(ComparisonRepository::class);
        $this->clock                 = new FrozenClock();
        $this->authorizations        = $this->createMock(AuthorizationsImpl::class);

        $this->service = new BaselineService(
            $this->baseline_repository,
            $this->comparison_repository,
            $this->clock,
            $this->authorizations
        );
    }

    private ProjectIdentifier $a_project;

    /** @before */
    public function createEntities(): void
    {
        $this->a_project = ProjectFactory::one();
    }

    public function testCreateWithoutSnapshotDateAddsGivenBaselineWithNowAsSnapshotDate(): void
    {
        $this->authorizations->method('canCreateBaseline')->willReturn(true);

        $baseline = TransientBaselineFactory::one()
            ->snapshotDate(null)
            ->build();
        $this->baseline_repository
            ->expects(self::atLeast(1))
            ->method('add')
            ->with($baseline, $this->current_user, $this->clock->now());

        $this->service->create($this->current_user, $baseline);
    }

    public function testCreateWithSnapshotDateAddsBaselineWithGivenSnapshotDate(): void
    {
        $this->authorizations->method('canCreateBaseline')->willReturn(true);

        $snapshot_date = DateTimeFactory::one();
        $baseline      = TransientBaselineFactory::one()
            ->snapshotDate($snapshot_date)
            ->build();
        $this->baseline_repository
            ->expects(self::atLeast(1))
            ->method('add')
            ->with($baseline, $this->current_user, $snapshot_date);

        $this->service->create($this->current_user, $baseline);
    }

    public function testCreateThrowsNotAuthorizedExceptionWhenNotAuthorized(): void
    {
        $this->expectException(NotAuthorizedException::class);

        $baseline = TransientBaselineFactory::one()->build();
        $this->authorizations->method('canCreateBaseline')
            ->with($this->current_user, $baseline)
            ->willReturn(false);

        $this->service->create($this->current_user, $baseline);
    }

    public function testDeleteDeletesGivenBaseline(): void
    {
        $this->authorizations->method('canDeleteBaseline')->willReturn(true);

        $this->comparison_repository->method('countByBaseline')->willReturn(0);

        $baseline = BaselineFactory::one()->build();
        $this->baseline_repository
            ->expects(self::atLeast(1))
            ->method('delete')
            ->with($baseline);

        $this->service->delete($this->current_user, $baseline);
    }

    public function testDeleteThrowsNotAuthorizedExceptionWhenNotAuthorized(): void
    {
        $this->expectException(NotAuthorizedException::class);

        $baseline = BaselineFactory::one()->build();
        $this->authorizations->method('canDeleteBaseline')
            ->with($this->current_user, $baseline)
            ->willReturn(false);

        $this->service->delete($this->current_user, $baseline);
    }

    public function testFindByProject(): void
    {
        $this->authorizations->method('canReadBaselinesOnProject')->willReturn(true);

        $baselines = [BaselineFactory::one()->build()];
        $this->baseline_repository
            ->method('findByProject')
            ->with($this->current_user, $this->a_project, 10, 3)
            ->willReturn($baselines);
        $this->baseline_repository
            ->method('countByProject')
            ->with($this->a_project)
            ->willReturn(233);

        $baselines_page = $this->service->findByProject($this->current_user, $this->a_project, 10, 3);

        self::assertEquals($baselines, $baselines_page->getBaselines());
        self::assertEquals(233, $baselines_page->getTotalBaselineCount());
        self::assertEquals(10, $baselines_page->getPageSize());
        self::assertEquals(3, $baselines_page->getBaselineOffset());
    }

    public function testFindByProjectThrowsNotAuthorizedExceptionWhenNotAuthorized(): void
    {
        $this->expectException(NotAuthorizedException::class);

        $project = ProjectFactory::one();
        $this->authorizations->method('canReadBaselinesOnProject')
            ->with($this->current_user, $project)
            ->willReturn(false);

        $this->service->findByProject($this->current_user, $project, 10, 0);
    }
}
