<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\REST {
    /* This is a trick to allow unit-testing this controller that sends headers. */
    function header($header, $replace = true, $http_response_code = null): void
    {
        \Tuleap\header($header, $replace, $http_response_code);
    }
}
namespace Tuleap\AgileDashboard\REST\v1\Milestone {

    use Mockery as M;
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use PHPUnit\Framework\TestCase;
    use Tuleap\AgileDashboard\Milestone\PaginatedMilestones;
    use Tuleap\AgileDashboard\Milestone\Request\FilteringQueryParser;
    use Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation;
    use Tuleap\Test\Builders\UserTestBuilder;
    use Tuleap\Test\Network\HTTPHeaderStack;

    final class ProjectMilestonesResourceTest extends TestCase
    {
        use MockeryPHPUnitIntegration;

        /**
         * @var ProjectMilestonesResource
         */
        private $controller;
        /**
         * @var M\LegacyMockInterface|M\MockInterface|\Planning_MilestoneFactory
         */
        private $milestone_factory;
        /**
         * @var M\LegacyMockInterface|M\MockInterface|MilestoneRepresentationBuilder
         */
        private $milestone_representation_builder;

        protected function setUp(): void
        {
            $this->milestone_factory                = M::mock(\Planning_MilestoneFactory::class);
            $this->milestone_representation_builder = M::mock(MilestoneRepresentationBuilder::class);
            $this->controller                       = new ProjectMilestonesResource(
                new FilteringQueryParser(),
                $this->milestone_factory,
                $this->milestone_representation_builder
            );
        }

        protected function tearDown(): void
        {
            HTTPHeaderStack::clear();
        }

        public function testItReturnsMilestoneRepresentations(): void
        {
            $user                = UserTestBuilder::aUser()->build();
            $project             = \Project::buildForTest();
            $query               = '';
            $representation_type = MilestoneRepresentation::SLIM;

            $first_milestone  = M::mock(\Planning_ArtifactMilestone::class);
            $second_milestone = M::mock(\Planning_ArtifactMilestone::class);
            $milestones       = new PaginatedMilestones([$first_milestone, $second_milestone], 2);
            $this->milestone_factory->shouldReceive('getPaginatedTopMilestones')
                ->once()
                ->andReturn($milestones);

            $first_representation  = M::mock(MilestoneRepresentation::class);
            $second_representation = M::mock(MilestoneRepresentation::class);
            $representations       = new PaginatedMilestonesRepresentations(
                [$first_representation, $second_representation],
                2
            );
            $this->milestone_representation_builder->shouldReceive('buildRepresentationsFromCollection')
                ->once()
                ->with($milestones, $user, $representation_type)
                ->andReturn($representations);

            $result  = $this->controller->get(
                $user,
                $project,
                $representation_type,
                $query,
                50,
                0,
                'asc'
            );
            $headers = HTTPHeaderStack::getStack();
            $this->assertEquals('X-PAGINATION-SIZE: 2', $headers[2]->getHeader());
            $this->assertContains($first_representation, $result);
            $this->assertContains($second_representation, $result);
        }

        public function testItThrowsBadRequestWhenQueryIsMalformed(): void
        {
            $user    = UserTestBuilder::aUser()->build();
            $project = \Project::buildForTest();
            $query   = 'null';

            $this->expectExceptionCode(400);
            $this->controller->get($user, $project, MilestoneRepresentation::SLIM, $query, 50, 0, 'asc');
        }

        public function testItReturnsEmptyArrayWhenNoPlanning(): void
        {
            $user    = UserTestBuilder::aUser()->build();
            $project = \Project::buildForTest();
            $query   = '';
            $this->milestone_factory->shouldReceive('getPaginatedTopMilestones')
                ->once()
                ->andThrow(new \Planning_NoPlanningsException());

            $representations = $this->controller->get(
                $user,
                $project,
                MilestoneRepresentation::SLIM,
                $query,
                50,
                0,
                'asc'
            );
            $this->assertEmpty($representations);
            $headers = HTTPHeaderStack::getStack();
            $this->assertEquals('X-PAGINATION-SIZE: 0', $headers[2]->getHeader());
        }
    }
}
