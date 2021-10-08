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

use Transition;
use Tuleap\GlobalLanguageMock;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class AddToTopBacklogPostActionFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @var int
     */
    private $transition_id;
    /**
     * @var Transition
     */
    private $transition;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AddToTopBacklogPostActionDAO
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Workflow
     */
    private $workflow;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(AddToTopBacklogPostActionDAO::class);

        $workflow_id    = 112;
        $this->workflow = $this->createConfiguredMock(
            \Workflow::class,
            [
                'getId' => (string) $workflow_id,
                'getTracker' =>
                    TrackerTestBuilder::aTracker()
                        ->withProject(new \Project(['group_id' => 101]))->build()
            ]
        );

        $this->transition_id = 923;
        $this->transition    = new Transition($this->transition_id, $workflow_id, null, null);
        $this->transition->setWorkflow($this->workflow);
    }

    public function testBuildsThePostAction(): void
    {
        $this->dao->method('searchByTransitionID')->with($this->transition_id)->willReturn(['id' => 88]);

        $factory = new AddToTopBacklogPostActionFactory(
            $this->dao,
            BuildProgramStub::stubValidProgram(),
            $this->createMock(TopBacklogChangeProcessor::class)
        );

        $post_actions = $factory->loadPostActions($this->transition);
        self::assertCount(1, $post_actions);

        self::assertInstanceOf(AddToTopBacklogPostAction::class, $post_actions[0]);
        self::assertEquals(88, $post_actions[0]->getId());
    }

    public function testDoesNotBuildThePostActionIfWeAreOutsideOfAProgram(): void
    {
        $this->dao->expects(self::never())->method('searchByTransitionId');

        $factory = new AddToTopBacklogPostActionFactory(
            $this->dao,
            BuildProgramStub::stubInvalidProgram(),
            $this->createMock(TopBacklogChangeProcessor::class)
        );

        $post_actions = $factory->loadPostActions($this->transition);
        self::assertEmpty($post_actions);
    }

    public function testWarmsUpTheCacheBeforeGettingThePostAction(): void
    {
        $this->dao->method('searchByWorkflow')
            ->with($this->workflow)
            ->willReturn(
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
        $this->dao->expects(self::never())->method('searchByTransitionId');

        $factory = new AddToTopBacklogPostActionFactory(
            $this->dao,
            BuildProgramStub::stubValidProgram(),
            $this->createMock(TopBacklogChangeProcessor::class)
        );

        $factory->warmUpCacheForWorkflow($this->workflow);
        $post_actions = $factory->loadPostActions($this->transition);
        self::assertCount(1, $post_actions);

        self::assertInstanceOf(AddToTopBacklogPostAction::class, $post_actions[0]);
        self::assertEquals(88, $post_actions[0]->getId());
    }
}
