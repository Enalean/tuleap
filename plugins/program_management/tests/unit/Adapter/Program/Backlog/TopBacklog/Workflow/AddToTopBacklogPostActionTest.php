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
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field;
use Tuleap\GlobalLanguageMock;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Workflow\PostAction\Visitor;

final class AddToTopBacklogPostActionTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BuildProgram
     */
    private $build_program;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TopBacklogChangeProcessor
     */
    private $top_backlog_change_processor;
    /**
     * @var AddToTopBacklogPostAction
     */
    private $post_action;

    protected function setUp(): void
    {
        $this->build_program                = \Mockery::mock(BuildProgram::class);
        $this->top_backlog_change_processor = \Mockery::mock(TopBacklogChangeProcessor::class);

        $this->post_action = new AddToTopBacklogPostAction(
            \Mockery::mock(\Transition::class),
            1,
            $this->build_program,
            $this->top_backlog_change_processor
        );
    }

    public function testHasAShortName(): void
    {
        self::assertNotEmpty($this->post_action->getShortName());
    }

    public function testDoesNotBypassPermissions(): void
    {
        self::assertFalse($this->post_action->bypassPermissions(\Mockery::mock(Tracker_FormElement_Field::class)));
    }

    public function testTraverseVisitorAsAnExternalAction(): void
    {
        $visitor = \Mockery::mock(Visitor::class);
        $visitor->shouldReceive('visitExternalActions')->once();

        $this->post_action->accept($visitor);
    }

    public function testAddArtifactToTheTopBacklogOnceTheTransitionIsExecuted(): void
    {
        $changeset = \Mockery::mock(Tracker_Artifact_Changeset::class);
        $artifact  = \Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(999);
        $changeset->shouldReceive('getArtifact')->andReturn($artifact);
        $tracker = \Mockery::mock(Tracker::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $tracker->shouldReceive('getGroupId')->andReturn('102');
        $this->build_program->shouldReceive('buildExistingProgramProject')->andReturn(
            ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 102, UserTestBuilder::aUser()->build())
        );

        $this->top_backlog_change_processor->shouldReceive('processTopBacklogChangeForAProgram')->once();

        $this->post_action->after($changeset);
    }

    public function testDoesNothingIfSomeReasonWeTryToProcessAnArtifactThatIsNotPartOfAProgram(): void
    {
        $changeset = \Mockery::mock(Tracker_Artifact_Changeset::class);
        $artifact  = \Mockery::mock(Artifact::class);
        $changeset->shouldReceive('getArtifact')->andReturn($artifact);
        $tracker = \Mockery::mock(Tracker::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $tracker->shouldReceive('getGroupId')->andReturn('103');
        $this->build_program->shouldReceive('buildExistingProgramProject')->andThrow(new ProjectIsNotAProgramException(103));

        $this->top_backlog_change_processor->shouldNotReceive('processTopBacklogChangeForAProgram');

        $this->post_action->after($changeset);
    }
}
