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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use Project;
use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Factory\ComparisonFactory;
use Tuleap\Baseline\Factory\ProjectFactory;
use Tuleap\Baseline\Factory\TransientComparisonFactory;
use Tuleap\Baseline\Support\CurrentUserContext;

class ComparisonServiceTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use CurrentUserContext;

    /** @var ComparisonService */
    private $service;

    /** @var ComparisonRepository|MockInterface */
    private $comparison_repository;

    /** @var Authorizations|MockInterface */
    private $authorizations;

    /** @before */
    protected function createInstance(): void
    {
        $this->comparison_repository = Mockery::mock(ComparisonRepository::class);
        $this->authorizations        = Mockery::mock(AuthorizationsImpl::class);

        $this->service = new ComparisonService(
            $this->comparison_repository,
            $this->authorizations
        );
    }

    /** @var PFUser */
    private $a_user;

    /** @var Project|MockInterface */
    private $a_project;

    /** @before */
    public function createEntities()
    {
        $this->a_user    = new PFUser();
        $this->a_project = ProjectFactory::one();
    }

    public function testCreateThrowsWhenGivenBaselinesAreNotOnSameRootArtifacts()
    {
        $this->expectException(InvalidComparisonException::class);

        $artifact1 = BaselineArtifactFactory::one()->build();
        $artifact2 = BaselineArtifactFactory::one()->build();

        $comparison = TransientComparisonFactory::one()
            ->base(BaselineFactory::one()->artifact($artifact1)->build())
            ->comparedTo(BaselineFactory::one()->artifact($artifact2)->build())
            ->build();

        $this->authorizations
            ->shouldReceive(['canCreateComparison' => true]);

        $this->service->create($comparison, $this->current_user);

        $this->comparison_repository
            ->shouldReceive('add')
            ->never();
    }

    public function testDeleteDeletesGivenBaseline()
    {
        $this->authorizations->allows(['canDeleteComparison' => true]);

        $comparison = ComparisonFactory::one();
        $this->comparison_repository
            ->shouldReceive('delete')
            ->with($comparison, $this->current_user);

        $this->service->delete($this->current_user, $comparison);
    }

    public function testDeleteThrowsNotAuthorizedExceptionWhenNotAuthorized()
    {
        $this->expectException(NotAuthorizedException::class);

        $comparison = ComparisonFactory::one();
        $this->authorizations->allows()
            ->canDeleteComparison($this->current_user, $comparison)
            ->andReturn(false);

        $this->service->delete($this->current_user, $comparison);
    }

    public function testFinByProject()
    {
        $comparisons = [ComparisonFactory::one()];
        $this->comparison_repository
            ->shouldReceive('findByProject')
            ->with($this->a_user, $this->a_project, 10, 3)
            ->andReturn($comparisons);
        $this->comparison_repository
            ->shouldReceive('countByProject')
            ->with($this->a_project)
            ->andReturn(233);

        $this->authorizations
            ->shouldReceive(['canReadComparisonsOnProject' => true]);

        $comparisons_page = $this->service->findByProject($this->a_user, $this->a_project, 10, 3);

        $this->assertEquals($comparisons, $comparisons_page->getComparisons());
        $this->assertEquals(233, $comparisons_page->getTotalComparisonsCount());
        $this->assertEquals(10, $comparisons_page->getPageSize());
        $this->assertEquals(3, $comparisons_page->getComparisonOffset());
    }
}
