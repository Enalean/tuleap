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

use PHPUnit\Framework\MockObject\MockObject;
use Transition;
use Tuleap\ProgramManagement\Adapter\Workspace\ProgramServiceIsEnabledCertifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\ProgramService;
use Tuleap\ProgramManagement\Tests\Stub\CreatePostActionStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchByTransitionIdStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchByWorkflowStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AddToTopBacklogPostActionFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRANSITION_ID = 923;
    private const PROJECT_ID    = 101;
    private Transition $transition;
    private \Workflow&MockObject $workflow;
    private SearchByTransitionIdStub $search_transition;
    private SearchByWorkflowStub $search_by_workflow;
    private \Project $project;

    protected function setUp(): void
    {
        $workflow_id = 112;

        $this->project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)
            ->withUsedService(ProgramService::SERVICE_SHORTNAME)
            ->build();

        $this->workflow = $this->createConfiguredMock(
            \Workflow::class,
            [
                'getId'      => (string) $workflow_id,
                'getTracker' =>
                    TrackerTestBuilder::aTracker()
                        ->withProject($this->project)
                        ->build(),
            ]
        );

        $this->transition = new Transition(
            (string) self::TRANSITION_ID,
            (string) $workflow_id,
            null,
            new \Tracker_FormElement_Field_List_Bind_StaticValue(1, 'field', '', 1, false)
        );
        $this->transition->setWorkflow($this->workflow);
        $this->search_transition  = SearchByTransitionIdStub::withTransitions(['id' => 88]);
        $this->search_by_workflow = SearchByWorkflowStub::withoutTransitions();
    }

    private function getFactory(): AddToTopBacklogPostActionFactory
    {
        return new AddToTopBacklogPostActionFactory(
            $this->search_transition,
            RetrieveFullProjectStub::withProject($this->project),
            new ProgramServiceIsEnabledCertifier(),
            $this->createMock(TopBacklogChangeProcessor::class),
            $this->search_by_workflow,
            CreatePostActionStub::withCount()
        );
    }

    public function testBuildsThePostAction(): void
    {
        $post_actions = $this->getFactory()->loadPostActions($this->transition);
        self::assertCount(1, $post_actions);

        self::assertInstanceOf(AddToTopBacklogPostAction::class, $post_actions[0]);
        self::assertSame(88, $post_actions[0]->getId());
    }

    public function testDoesNotBuildThePostActionIfWeAreOutsideOfAProgram(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)
            ->withoutServices()
            ->build();

        $post_actions = $this->getFactory()->loadPostActions($this->transition);
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
                'transition_id' => self::TRANSITION_ID,
            ],
        ];

        $this->search_transition  = SearchByTransitionIdStub::withoutTransitions();
        $this->search_by_workflow = SearchByWorkflowStub::withTransitions($transitions);

        $factory = $this->getFactory();
        $factory->warmUpCacheForWorkflow($this->workflow);
        $post_actions = $factory->loadPostActions($this->transition);
        self::assertCount(1, $post_actions);

        self::assertInstanceOf(AddToTopBacklogPostAction::class, $post_actions[0]);
        self::assertSame(88, $post_actions[0]->getId());
    }
}
