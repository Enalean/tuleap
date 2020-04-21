<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_Milestone_MilestoneRepresentationBuilder;
use AgileDashboard_Milestone_PaginatedMilestones;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Project;
use Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation;

class FutureMilestoneRepresentationBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var FutureMilestoneRepresentationBuilder
     */
    private $builder;
    /**
     * @var PFUser
     */
    private $john_doe;
    /**
     * @var Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var AgileDashboard_Milestone_MilestoneRepresentationBuilder|Mockery\MockInterface
     */
    private $representation_builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->john_doe = \Mockery::mock(PFUser::class);
        $this->john_doe->shouldReceive('getId')->andReturn(200);

        $this->milestone_factory = Mockery::mock(Planning_MilestoneFactory::class);

        $this->representation_builder = Mockery::mock(AgileDashboard_Milestone_MilestoneRepresentationBuilder::class);

        $this->builder = new FutureMilestoneRepresentationBuilder($this->representation_builder, $this->milestone_factory);

        $this->project = Mockery::mock(Project::class);
    }

    public function testGetNothingIfNoFutureMilestones(): void
    {
        $this->milestone_factory
            ->shouldReceive('getPaginatedTopMilestonesInTheFuture')
            ->andReturn(new AgileDashboard_Milestone_PaginatedMilestones([], 0));

        $response = $this->builder->getPaginatedTopMilestonesRepresentations(
            $this->project,
            $this->john_doe,
            'all',
            0,
            0,
            ''
        );

        $this->assertEmpty($response->getTotalSize());
        $this->assertEmpty($response->getMilestonesRepresentations());
    }

    public function testGetAllFutureMilestones(): void
    {
        $milestone1 = Mockery::mock(Planning_Milestone::class);
        $milestone2 = Mockery::mock(Planning_Milestone::class);
        $milestone3 = Mockery::mock(Planning_Milestone::class);

        $this->milestone_factory
            ->shouldReceive('getPaginatedTopMilestonesInTheFuture')
            ->andReturn(new AgileDashboard_Milestone_PaginatedMilestones([$milestone1, $milestone2, $milestone3], 3));

        $this->representation_builder
            ->shouldReceive('getMilestoneRepresentation')
            ->andReturn(Mockery::mock(MilestoneRepresentation::class));

        $response = $this->builder->getPaginatedTopMilestonesRepresentations(
            $this->project,
            $this->john_doe,
            'all',
            0,
            0,
            ''
        );

        $this->assertEquals(3, $response->getTotalSize());
        $this->assertCount(3, $response->getMilestonesRepresentations());
    }
}
