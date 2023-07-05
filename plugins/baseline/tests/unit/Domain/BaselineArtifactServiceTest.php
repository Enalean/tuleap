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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\Support\CurrentUserContext;

final class BaselineArtifactServiceTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use CurrentUserContext;

    /** @var BaselineArtifactService */
    private $service;

    /** @var BaselineArtifactRepository&MockObject */
    private $baseline_artifact_repository;

    /** @before */
    public function createInstance(): void
    {
        $this->baseline_artifact_repository = $this->createMock(BaselineArtifactRepository::class);
        $this->service                      = new BaselineArtifactService($this->baseline_artifact_repository);
    }

    public function testFindByBaselineAndIds(): void
    {
        $baseline  = BaselineFactory::one()->build();
        $artifact1 = BaselineArtifactFactory::one()->build();
        $artifact2 = BaselineArtifactFactory::one()->build();

        $this->baseline_artifact_repository
            ->method('findByIdAt')
            ->willReturnMap([
                [$this->current_user, 1, $baseline->getSnapshotDate(), $artifact1],
                [$this->current_user, 2, $baseline->getSnapshotDate(), $artifact2],
            ]);

        $artifacts = $this->service->findByBaselineAndIds($this->current_user, $baseline, [1, 2]);

        self::assertEquals([$artifact1, $artifact2], $artifacts);
    }

    public function testFindByBaselineAndIdsThrowsWhenNoArtifactFound(): void
    {
        $this->expectException(BaselineArtifactNotFoundException::class);

        $baseline = BaselineFactory::one()->build();

        $this->baseline_artifact_repository
            ->method('findByIdAt')
            ->willReturnMap([
                [$this->current_user, 1, $baseline->getSnapshotDate(), BaselineArtifactFactory::one()->build()],
                [$this->current_user, 2, $baseline->getSnapshotDate(), null],
                [$this->current_user, 3, $baseline->getSnapshotDate(), BaselineArtifactFactory::one()->build()],
            ]);

        $this->service->findByBaselineAndIds($this->current_user, $baseline, [1, 2, 3]);
    }
}
