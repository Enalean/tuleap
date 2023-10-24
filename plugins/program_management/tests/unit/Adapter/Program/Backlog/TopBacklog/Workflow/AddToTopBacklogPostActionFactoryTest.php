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
use Tuleap\ProgramManagement\Tests\Stub\CreatePostActionStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchByTransitionIdStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchByWorkflowStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class AddToTopBacklogPostActionFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private int $transition_id;
    private Transition $transition;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Workflow
     */
    private $workflow;

    protected function setUp(): void
    {
        $workflow_id    = 112;
        $this->workflow = $this->createConfiguredMock(
            \Workflow::class,
            [
                'getId'      => (string) $workflow_id,
                'getTracker' =>
                    TrackerTestBuilder::aTracker()
                        ->withProject(new \Project(['group_id' => 101]))->build(),
            ]
        );

        $this->transition_id = 923;
        $this->transition    = new Transition((string) $this->transition_id, (string) $workflow_id, null, new \Tracker_FormElement_Field_List_Bind_StaticValue(1, 'field', "", 1, false));
        $this->transition->setWorkflow($this->workflow);
    }

    public function testBuildsThePostAction(): void
    {
        $factory = new AddToTopBacklogPostActionFactory(
            SearchByTransitionIdStub::withTransitions(['id' => 88]),
            BuildProgramStub::stubValidProgram(),
            $this->createMock(TopBacklogChangeProcessor::class),
            SearchByWorkflowStub::withoutTransitions(),
            CreatePostActionStub::withCount()
        );

        $post_actions = $factory->loadPostActions($this->transition);
        self::assertCount(1, $post_actions);

        self::assertInstanceOf(AddToTopBacklogPostAction::class, $post_actions[0]);
        self::assertEquals(88, $post_actions[0]->getId());
    }

    public function testDoesNotBuildThePostActionIfWeAreOutsideOfAProgram(): void
    {
        $factory = new AddToTopBacklogPostActionFactory(
            SearchByTransitionIdStub::withoutTransitions(),
            BuildProgramStub::stubInvalidProgram(),
            $this->createMock(TopBacklogChangeProcessor::class),
            SearchByWorkflowStub::withoutTransitions(),
            CreatePostActionStub::withCount()
        );

        $post_actions = $factory->loadPostActions($this->transition);
        self::assertEmpty($post_actions);
    }

    public function testWarmsUpTheCacheBeforeGettingThePostAction(): void
    {
        $transitions = [
            [
                'id'            => 2,
                'transition_id' => 329,
            ],
            [
                'id'            => 88,
                'transition_id' => $this->transition_id,
            ],
        ];

        $factory = new AddToTopBacklogPostActionFactory(
            SearchByTransitionIdStub::withoutTransitions(),
            BuildProgramStub::stubValidProgram(),
            $this->createMock(TopBacklogChangeProcessor::class),
            SearchByWorkflowStub::withTransitions($transitions),
            CreatePostActionStub::withCount()
        );

        $factory->warmUpCacheForWorkflow($this->workflow);
        $post_actions = $factory->loadPostActions($this->transition);
        self::assertCount(1, $post_actions);

        self::assertInstanceOf(AddToTopBacklogPostAction::class, $post_actions[0]);
        self::assertEquals(88, $post_actions[0]->getId());
    }
}
