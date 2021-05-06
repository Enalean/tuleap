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
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field;
use Tuleap\GlobalLanguageMock;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Workflow\PostAction\Visitor;

final class AddToTopBacklogPostActionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var BuildProgram
     */
    private $build_program;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TopBacklogChangeProcessor
     */
    private $top_backlog_change_processor;

    protected function setUp(): void
    {
        $this->build_program                = BuildProgramStub::stubValidProgram();
        $this->top_backlog_change_processor = \Mockery::mock(TopBacklogChangeProcessor::class);
    }

    public function testHasAShortName(): void
    {
        self::assertNotEmpty($this->getPostAction()->getShortName());
    }

    public function testDoesNotBypassPermissions(): void
    {
        self::assertFalse($this->getPostAction()->bypassPermissions(\Mockery::mock(Tracker_FormElement_Field::class)));
    }

    public function testTraverseVisitorAsAnExternalAction(): void
    {
        $visitor = \Mockery::mock(Visitor::class);
        $visitor->shouldReceive('visitExternalActions')->once();

        $this->getPostAction()->accept($visitor);
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

        $this->top_backlog_change_processor->shouldReceive('processTopBacklogChangeForAProgram')->once();

        $this->getPostAction()->after($changeset);
    }

    public function testDoesNothingIfSomeReasonWeTryToProcessAnArtifactThatIsNotPartOfAProgram(): void
    {
        $changeset = \Mockery::mock(Tracker_Artifact_Changeset::class);
        $artifact  = \Mockery::mock(Artifact::class);
        $changeset->shouldReceive('getArtifact')->andReturn($artifact);
        $tracker = \Mockery::mock(Tracker::class);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $tracker->shouldReceive('getGroupId')->andReturn('103');
        $this->build_program = BuildProgramStub::stubInvalidProgram();

        $this->top_backlog_change_processor->shouldNotReceive('processTopBacklogChangeForAProgram');

        $this->getPostAction()->after($changeset);
    }

    private function getPostAction(): AddToTopBacklogPostAction
    {
        return new AddToTopBacklogPostAction(
            \Mockery::mock(\Transition::class),
            1,
            $this->build_program,
            $this->top_backlog_change_processor
        );
    }
}
