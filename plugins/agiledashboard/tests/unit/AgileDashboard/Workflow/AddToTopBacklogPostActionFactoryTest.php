<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Workflow;

use PHPUnit\Framework\MockObject\MockObject;
use Transition;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AddToTopBacklogPostActionFactoryTest extends TestCase
{
    private AddToTopBacklogPostActionFactory $factory;
    private AddToTopBacklogPostActionDao&MockObject $add_to_top_backlog_post_action_dao;
    private Transition $transition;
    private ExplicitBacklogDao&MockObject $explicit_backlog_dao;
    private Workflow&MockObject $workflow;
    private int $transition_id;

    #[\Override]
    protected function setUp(): void
    {
        $this->add_to_top_backlog_post_action_dao = $this->createMock(AddToTopBacklogPostActionDao::class);
        $unplanned_artifacts_adder                = $this->createMock(UnplannedArtifactsAdder::class);
        $this->explicit_backlog_dao               = $this->createMock(ExplicitBacklogDao::class);

        $this->factory = new AddToTopBacklogPostActionFactory(
            $this->add_to_top_backlog_post_action_dao,
            $unplanned_artifacts_adder,
            $this->explicit_backlog_dao
        );

        $workflow_id    = 112;
        $this->workflow = $this->createMock(Workflow::class);
        $this->workflow->method('getId')->willReturn((string) $workflow_id);
        $this->workflow->method('getTracker')->willReturn(
            TrackerTestBuilder::aTracker()
                ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
                ->build()
        );

        $this->transition_id = 923;
        $this->transition    = new Transition(
            (string) $this->transition_id,
            (string) $workflow_id,
            null,
            ListStaticValueBuilder::aStaticValue('field')->build()
        );
        $this->transition->setWorkflow($this->workflow);
    }

    public function testItBuildsThePostAction()
    {
        $this->explicit_backlog_dao->expects($this->once())->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->add_to_top_backlog_post_action_dao->method('searchByTransitionId')
            ->with($this->transition_id)
            ->willReturn([
                'id'            => 1,
                'transition_id' => $this->transition_id,
            ]);

        $post_actions = $this->factory->loadPostActions($this->transition);
        self::assertCount(1, $post_actions);

        self::assertInstanceOf(AddToTopBacklog::class, $post_actions[0]);
        self::assertEquals(1, $post_actions[0]->getId());
    }

    public function testItDoesNotBuildThePostActionIfProjectDoesNotUseExplicitTopBacklogManagement()
    {
        $this->explicit_backlog_dao->expects($this->once())->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(false);

        $this->add_to_top_backlog_post_action_dao->expects($this->never())->method('searchByTransitionId');

        $post_actions = $this->factory->loadPostActions($this->transition);
        self::assertEmpty($post_actions);
    }

    public function testItWarmsTheCacheUpBeforeGettingThePostAction()
    {
        $this->explicit_backlog_dao->expects($this->once())->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->add_to_top_backlog_post_action_dao->method('searchByWorkflow')
            ->with($this->workflow)
            ->willReturn([
                [
                    'id'            => 2,
                    'transition_id' => 329,
                ],
                [
                    'id'            => 1,
                    'transition_id' => $this->transition_id,
                ],
            ]);

        $this->factory->warmUpCacheForWorkflow($this->workflow);
        $post_actions = $this->factory->loadPostActions($this->transition);
        self::assertCount(1, $post_actions);

        self::assertInstanceOf(AddToTopBacklog::class, $post_actions[0]);
        self::assertEquals(1, $post_actions[0]->getId());
    }
}
