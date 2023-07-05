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
use Tuleap\Baseline\Adapter\UserProxy;
use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Factory\ComparisonFactory;
use Tuleap\Baseline\Factory\ProjectFactory;
use Tuleap\Baseline\Factory\TransientComparisonFactory;
use Tuleap\Baseline\Support\CurrentUserContext;
use Tuleap\Test\Builders\UserTestBuilder;

final class ComparisonServiceTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use CurrentUserContext;

    /** @var ComparisonService */
    private $service;

    /** @var ComparisonRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $comparison_repository;

    /** @var Authorizations&\PHPUnit\Framework\MockObject\MockObject */
    private $authorizations;
    private UserProxy $a_user;

    /** @before */
    protected function createInstance(): void
    {
        $this->comparison_repository = $this->createMock(ComparisonRepository::class);
        $this->authorizations        = $this->createMock(AuthorizationsImpl::class);

        $this->service = new ComparisonService(
            $this->comparison_repository,
            $this->authorizations
        );
    }

    private ProjectIdentifier $a_project;

    /** @before */
    public function createEntities(): void
    {
        $this->a_user    = UserProxy::fromUser(UserTestBuilder::aUser()->build());
        $this->a_project = ProjectFactory::one();
    }

    public function testCreateThrowsWhenGivenBaselinesAreNotOnSameRootArtifacts(): void
    {
        $this->expectException(InvalidComparisonException::class);

        $artifact1 = BaselineArtifactFactory::one()->id(1)->build();
        $artifact2 = BaselineArtifactFactory::one()->id(2)->build();

        $comparison = TransientComparisonFactory::one()
            ->base(BaselineFactory::one()->artifact($artifact1)->build())
            ->comparedTo(BaselineFactory::one()->artifact($artifact2)->build())
            ->build();

        $this->authorizations->method('canCreateComparison')->willReturn(true);

        $this->service->create($comparison, $this->current_user);

        $this->comparison_repository->expects(self::never())->method('add');
    }

    public function testDeleteDeletesGivenBaseline(): void
    {
        $this->authorizations->method('canDeleteComparison')->willReturn(true);

        $comparison = ComparisonFactory::one();
        $this->comparison_repository
            ->expects(self::atLeast(1))
            ->method('delete')
            ->with($comparison, $this->current_user);

        $this->service->delete($this->current_user, $comparison);
    }

    public function testDeleteThrowsNotAuthorizedExceptionWhenNotAuthorized(): void
    {
        $this->expectException(NotAuthorizedException::class);

        $comparison = ComparisonFactory::one();
        $this->authorizations->method('canDeleteComparison')
            ->with($this->current_user, $comparison)
            ->willReturn(false);

        $this->service->delete($this->current_user, $comparison);
    }

    public function testFinByProject(): void
    {
        $comparisons = [ComparisonFactory::one()];
        $this->comparison_repository
            ->method('findByProject')
            ->with($this->a_user, $this->a_project, 10, 3)
            ->willReturn($comparisons);
        $this->comparison_repository
            ->method('countByProject')
            ->with($this->a_project)
            ->willReturn(233);

        $this->authorizations->method('canReadComparisonsOnProject')->willReturn(true);

        $comparisons_page = $this->service->findByProject($this->a_user, $this->a_project, 10, 3);

        self::assertEquals($comparisons, $comparisons_page->getComparisons());
        self::assertEquals(233, $comparisons_page->getTotalComparisonsCount());
        self::assertEquals(10, $comparisons_page->getPageSize());
        self::assertEquals(3, $comparisons_page->getComparisonOffset());
    }
}
