<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\Workflow;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Transition;
use Tuleap\GlobalLanguageMock;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class AddToTopBacklogPostActionFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AddToTopBacklogPostActionDAO
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Workflow
     */
    private $workflow;
    /**
     * @var int
     */
    private $transition_id;
    /**
     * @var Transition
     */
    private $transition;

    protected function setUp(): void
    {
        $this->dao = \Mockery::mock(AddToTopBacklogPostActionDAO::class);

        $workflow_id    = 112;
        $this->workflow = \Mockery::mock(
            \Workflow::class,
            [
                'getId' => (string) $workflow_id,
                'getTracker' =>
                    TrackerTestBuilder::aTracker()
                        ->withProject(new \Project(['group_id' => 101]))->build()
            ]
        );

        $this->transition_id = 923;
        $this->transition    = new Transition((string) $this->transition_id, (string) $workflow_id, null, null);
        $this->transition->setWorkflow($this->workflow);
    }

    public function testBuildsThePostAction(): void
    {
        $this->dao->shouldReceive('searchByTransitionID')->with($this->transition_id)->andReturn(['id' => 88]);

        $factory = new AddToTopBacklogPostActionFactory(
            $this->dao,
            BuildProgramStub::stubValidProgram(),
            \Mockery::mock(TopBacklogChangeProcessor::class)
        );

        $post_actions = $factory->loadPostActions($this->transition);
        self::assertCount(1, $post_actions);

        self::assertInstanceOf(AddToTopBacklogPostAction::class, $post_actions[0]);
        self::assertEquals(88, $post_actions[0]->getId());
    }

    public function testDoesNotBuildThePostActionIfWeAreOutsideOfAProgram(): void
    {
        $this->dao->shouldNotReceive('searchByTransitionId');

        $factory = new AddToTopBacklogPostActionFactory(
            $this->dao,
            BuildProgramStub::stubInvalidProgram(),
            \Mockery::mock(TopBacklogChangeProcessor::class)
        );

        $post_actions = $factory->loadPostActions($this->transition);
        self::assertEmpty($post_actions);
    }

    public function testWarmsUpTheCacheBeforeGettingThePostAction(): void
    {
        $this->dao->shouldReceive('searchByWorkflow')
            ->with($this->workflow)
            ->andReturn(
                [
                    [
                        'id' => 2,
                        'transition_id' => 329
                    ],
                    [
                        'id' => 88,
                        'transition_id' => $this->transition_id
                    ],
                ]
            );
        $this->dao->shouldNotReceive('searchByTransitionId');

        $factory = new AddToTopBacklogPostActionFactory(
            $this->dao,
            BuildProgramStub::stubValidProgram(),
            \Mockery::mock(TopBacklogChangeProcessor::class)
        );

        $factory->warmUpCacheForWorkflow($this->workflow);
        $post_actions = $factory->loadPostActions($this->transition);
        self::assertCount(1, $post_actions);

        self::assertInstanceOf(AddToTopBacklogPostAction::class, $post_actions[0]);
        self::assertEquals(88, $post_actions[0]->getId());
    }
}
