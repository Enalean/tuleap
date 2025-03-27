<?php
/**
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

    use PHPUnit\Framework\MockObject\MockObject;
    use Planning_ArtifactMilestone;
    use Planning_MilestoneFactory;
    use Planning_NoPlanningsException;
    use Tuleap\AgileDashboard\Milestone\PaginatedMilestones;
    use Tuleap\AgileDashboard\Milestone\Request\FilteringQueryParser;
    use Tuleap\AgileDashboard\REST\v1\MilestoneRepresentation;
    use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
    use Tuleap\Test\Builders\ProjectTestBuilder;
    use Tuleap\Test\Builders\UserTestBuilder;
    use Tuleap\Test\Network\HTTPHeaderStack;
    use Tuleap\Test\PHPUnit\TestCase;
    use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

    #[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
    final class ProjectMilestonesResourceTest extends TestCase
    {
        private ProjectMilestonesResource $controller;
        private Planning_MilestoneFactory&MockObject $milestone_factory;
        private MilestoneRepresentationBuilder&MockObject $milestone_representation_builder;

        protected function setUp(): void
        {
            $this->milestone_factory                = $this->createMock(Planning_MilestoneFactory::class);
            $this->milestone_representation_builder = $this->createMock(MilestoneRepresentationBuilder::class);
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
            $project             = ProjectTestBuilder::aProject()->build();
            $query               = '';
            $representation_type = MilestoneRepresentation::SLIM;

            $milestone  = new Planning_ArtifactMilestone(
                $project,
                PlanningBuilder::aPlanning(101)->build(),
                ArtifactTestBuilder::anArtifact(154)->build(),
            );
            $milestones = new PaginatedMilestones([$milestone, $milestone], 2);
            $this->milestone_factory->expects($this->once())->method('getPaginatedTopMilestones')->willReturn($milestones);

            $first_representation  = $this->createMock(MilestoneRepresentation::class);
            $second_representation = $this->createMock(MilestoneRepresentation::class);
            $representations       = new PaginatedMilestonesRepresentations(
                [$first_representation, $second_representation],
                2
            );
            $this->milestone_representation_builder->expects($this->once())->method('buildRepresentationsFromCollection')
                ->with($milestones, $user, $representation_type)
                ->willReturn($representations);

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
            self::assertEquals('X-PAGINATION-SIZE: 2', $headers[2]->getHeader());
            self::assertContains($first_representation, $result);
            self::assertContains($second_representation, $result);
        }

        public function testItThrowsBadRequestWhenQueryIsMalformed(): void
        {
            $user    = UserTestBuilder::aUser()->build();
            $project = ProjectTestBuilder::aProject()->build();
            $query   = 'null';

            self::expectExceptionCode(400);
            $this->controller->get($user, $project, MilestoneRepresentation::SLIM, $query, 50, 0, 'asc');
        }

        public function testItReturnsEmptyArrayWhenNoPlanning(): void
        {
            $user    = UserTestBuilder::aUser()->build();
            $project = ProjectTestBuilder::aProject()->build();
            $query   = '';
            $this->milestone_factory->expects($this->once())->method('getPaginatedTopMilestones')
                ->willThrowException(new Planning_NoPlanningsException());

            $representations = $this->controller->get(
                $user,
                $project,
                MilestoneRepresentation::SLIM,
                $query,
                50,
                0,
                'asc'
            );
            self::assertEmpty($representations);
            $headers = HTTPHeaderStack::getStack();
            self::assertEquals('X-PAGINATION-SIZE: 0', $headers[2]->getHeader());
        }
    }
}
