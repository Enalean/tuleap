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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Transition;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;

class AddToTopBacklogPostActionFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AddToTopBacklogPostActionFactory
     */
    private $factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AddToTopBacklogPostActionDao
     */
    private $add_to_top_backlog_post_action_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UnplannedArtifactsAdder
     */
    private $unplanned_artifacts_adder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Transition
     */
    private $transition;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Workflow
     */
    private $workflow;
    /**
     * @var int
     */
    private $transition_id;

    protected function setUp(): void
    {
        $this->add_to_top_backlog_post_action_dao = Mockery::mock(AddToTopBacklogPostActionDao::class);
        $this->unplanned_artifacts_adder          = Mockery::mock(UnplannedArtifactsAdder::class);
        $this->explicit_backlog_dao               = Mockery::mock(ExplicitBacklogDao::class);

        $this->factory = new AddToTopBacklogPostActionFactory(
            $this->add_to_top_backlog_post_action_dao,
            $this->unplanned_artifacts_adder,
            $this->explicit_backlog_dao
        );

        $workflow_id    = 112;
        $this->workflow = Mockery::mock(
            \Workflow::class,
            [
                'getId' => (string) $workflow_id,
                'getTracker' => Mockery::mock(\Tracker::class, ['getGroupId' => '101']),
            ]
        );

        $this->transition_id = 923;
        $this->transition    = new Transition((string) $this->transition_id, (string) $workflow_id, null, null);
        $this->transition->setWorkflow($this->workflow);
    }

    public function testItBuildsThePostAction()
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->once()
            ->andReturnTrue();

        $this->add_to_top_backlog_post_action_dao->shouldReceive('searchByTransitionId')
            ->with($this->transition_id)
            ->andReturn([
                'id' => 1,
                'transition_id' => $this->transition_id
            ]);

        $post_actions = $this->factory->loadPostActions($this->transition);
        $this->assertCount(1, $post_actions);

        $this->assertInstanceOf(AddToTopBacklog::class, $post_actions[0]);
        self::assertEquals(1, $post_actions[0]->getId());
    }

    public function testItDoesNotBuildThePostActionIfProjectDoesNotUseExplicitTopBacklogManagement()
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->once()
            ->andReturnFalse();

        $this->add_to_top_backlog_post_action_dao->shouldNotReceive('searchByTransitionId');

        $post_actions = $this->factory->loadPostActions($this->transition);
        $this->assertEmpty($post_actions);
    }


    public function testItWarmsTheCacheUpBeforeGettingThePostAction()
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->once()
            ->andReturnTrue();

        $this->add_to_top_backlog_post_action_dao->shouldReceive('searchByWorkflow')
            ->with($this->workflow)
            ->andReturn(
                [
                    [
                        'id' => 2,
                        'transition_id' => 329
                    ],
                    [
                        'id' => 1,
                        'transition_id' => $this->transition_id
                    ],
                ]
            );

        $this->factory->warmUpCacheForWorkflow($this->workflow);
        $post_actions = $this->factory->loadPostActions($this->transition);
        $this->assertCount(1, $post_actions);

        $this->assertInstanceOf(AddToTopBacklog::class, $post_actions[0]);
        self::assertEquals(1, $post_actions[0]->getId());
    }
}
