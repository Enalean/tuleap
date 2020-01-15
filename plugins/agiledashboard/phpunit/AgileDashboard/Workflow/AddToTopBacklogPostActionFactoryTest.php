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

    protected function setUp(): void
    {
        parent::setUp();

        $this->add_to_top_backlog_post_action_dao = Mockery::mock(AddToTopBacklogPostActionDao::class);
        $this->unplanned_artifacts_adder = Mockery::mock(UnplannedArtifactsAdder::class);

        $this->factory = new AddToTopBacklogPostActionFactory(
            $this->add_to_top_backlog_post_action_dao,
            $this->unplanned_artifacts_adder
        );

        $this->transition = Mockery::mock(Transition::class);
        $this->transition->shouldReceive('getId')->andReturn('923');
    }

    public function testItBuildsThePostAction()
    {
        $this->add_to_top_backlog_post_action_dao->shouldReceive('searchByTransitionId')
            ->with(923)
            ->andReturn([
                'id' => 1,
                'transition_id' => 923
            ]);

        $post_actions = $this->factory->loadPostActions($this->transition);
        $this->assertCount(1, $post_actions);

        $this->assertInstanceOf(AddToTopBacklog::class, $post_actions[0]);
    }
}
