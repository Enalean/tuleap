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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Factory\TransientComparisonFactory;
use Tuleap\Baseline\Support\CurrentUserContext;

class ComparisonServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use CurrentUserContext;

    /** @var ComparisonService */
    private $service;

    /** @var ComparisonRepository|MockInterface */
    private $comparison_repository;

    /** @before */
    protected function createInstance(): void
    {
        $this->comparison_repository = Mockery::mock(ComparisonRepository::class);
        $this->service               = new ComparisonService($this->comparison_repository);
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

        $this->service->create($comparison, $this->current_user);

        $this->comparison_repository
            ->shouldReceive('add')
            ->never();
    }
}
