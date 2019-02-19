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

namespace Tuleap\Baseline;

require_once __DIR__ . '/../bootstrap.php';

use DateTime;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tuleap\Baseline\Factory\ChangesetFactory;
use Tuleap\Baseline\Factory\MilestoneFactory;
use Tuleap\Baseline\Support\DateTimeFactory;

class BaselineServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var BaselineService */
    private $service;

    /** @var FieldRepository|MockInterface */
    private $field_repository;

    /** @var Permissions|MockInterface */
    private $permissions;

    /** @var ChangesetRepository|MockInterface */
    private $changeset_repository;

    /** @before */
    public function createInstance()
    {
        $this->field_repository     = Mockery::mock(FieldRepository::class)->shouldIgnoreMissing();
        $this->permissions          = Mockery::mock(Permissions::class)->shouldIgnoreMissing();
        $this->changeset_repository = Mockery::mock(ChangesetRepository::class)->shouldIgnoreMissing();

        $this->service = new BaselineService(
            $this->field_repository,
            $this->permissions,
            $this->changeset_repository
        );
    }

    /** @var Tracker_Artifact */
    private $a_milestone;

    /** @var DateTime */
    private $a_date;

    /** @before */
    public function createEntities()
    {
        $this->a_milestone = MilestoneFactory::one()->build();
        $this->a_date      = DateTimeFactory::one();
    }

    public function testFindSimplifiedThrowsWhenNoChangesetFound()
    {
        $this->expectException(ChangesetNotFoundException::class);

        $this->changeset_repository
            ->shouldReceive('findByArtifactAndDate')
            ->andReturn(null);

        $this->service->findSimplified($this->a_milestone, $this->a_date);
    }

    public function testFindSimplifiedReturnsNullTitleWhenNoCorrespondingSemanticDefined()
    {
        $this->changeset_repository
            ->shouldReceive('findByArtifactAndDate')
            ->andReturn(ChangesetFactory::one()->build());

        $this->field_repository
            ->shouldReceive('findTitleByTracker')
            ->andReturn(null);

        $baseline = $this->service->findSimplified($this->a_milestone, $this->a_date);

        $this->assertNull($baseline->getTitle());
    }

    public function testFindSimplifiedReturnsNullDescriptionWhenNoCorrespondingSemanticDefined()
    {
        $this->changeset_repository
            ->shouldReceive('findByArtifactAndDate')
            ->andReturn(ChangesetFactory::one()->build());

        $this->field_repository
            ->shouldReceive('findDescriptionByTracker')
            ->andReturn(null);

        $baseline = $this->service->findSimplified($this->a_milestone, $this->a_date);

        $this->assertNull($baseline->getDescription());
    }

    public function testFindSimplifiedReturnsNullStatusWhenNoCorrespondingSemanticDefined()
    {
        $this->changeset_repository
            ->shouldReceive('findByArtifactAndDate')
            ->andReturn(ChangesetFactory::one()->build());

        $this->field_repository
            ->shouldReceive('findStatusByTracker')
            ->andReturn(null);

        $baseline = $this->service->findSimplified($this->a_milestone, $this->a_date);

        $this->assertNull($baseline->getStatus());
    }
}
