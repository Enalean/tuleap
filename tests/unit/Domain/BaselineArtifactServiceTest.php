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
use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Support\CurrentUserContext;

class BaselineArtifactServiceTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use CurrentUserContext;

    /** @var BaselineArtifactService */
    private $service;

    /** @var BaselineArtifactRepository|MockInterface */
    private $baseline_artifact_repository;

    /** @before */
    public function createInstance()
    {
        $this->baseline_artifact_repository = Mockery::mock(BaselineArtifactRepository::class);
        $this->service                      = new BaselineArtifactService($this->baseline_artifact_repository);
    }

    public function testFindByBaselineAndIds()
    {
        $current_user = new PFUser();
        $baseline     = BaselineFactory::one()->build();

        $artifact1 = BaselineArtifactFactory::one()->build();
        $this->baseline_artifact_repository
            ->shouldReceive('findByIdAt')
            ->with($current_user, 1, $baseline->getSnapshotDate())
            ->andReturn($artifact1);

        $artifact2 = BaselineArtifactFactory::one()->build();
        $this->baseline_artifact_repository
            ->shouldReceive('findByIdAt')
            ->with($current_user, 2, $baseline->getSnapshotDate())
            ->andReturn($artifact2);

        $artifacts = $this->service->findByBaselineAndIds($current_user, $baseline, [1, 2]);

        $this->assertEquals([$artifact1, $artifact2], $artifacts);
    }

    public function testFindByBaselineAndIdsThrowsWhenNoArtifactFound()
    {
        $this->expectException(BaselineArtifactNotFoundException::class);

        $current_user = new PFUser();
        $baseline     = BaselineFactory::one()->build();

        $this->baseline_artifact_repository
            ->shouldReceive('findByIdAt')
            ->andReturn(BaselineArtifactFactory::one()->build())
            ->byDefault();

        $this->baseline_artifact_repository
            ->shouldReceive('findByIdAt')
            ->with($current_user, 2, $baseline->getSnapshotDate())
            ->andReturn(null);

        $this->service->findByBaselineAndIds($current_user, $baseline, [1, 2, 3]);
    }
}
