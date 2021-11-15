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

use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field;
use Tuleap\GlobalLanguageMock;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\PostAction\Visitor;

final class AddToTopBacklogPostActionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @var BuildProgram
     */
    private $build_program;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TopBacklogChangeProcessor
     */
    private $top_backlog_change_processor;

    protected function setUp(): void
    {
        $this->build_program                = BuildProgramStub::stubValidProgram();
        $this->top_backlog_change_processor = $this->createMock(TopBacklogChangeProcessor::class);
    }

    public function testHasAShortName(): void
    {
        self::assertNotEmpty($this->getPostAction()->getShortName());
    }

    public function testDoesNotBypassPermissions(): void
    {
        self::assertFalse($this->getPostAction()->bypassPermissions($this->createMock(Tracker_FormElement_Field::class)));
    }

    public function testTraverseVisitorAsAnExternalAction(): void
    {
        $visitor = $this->createMock(Visitor::class);
        $visitor->expects(self::once())->method('visitExternalActions');

        $this->getPostAction()->accept($visitor);
    }

    public function testAddArtifactToTheTopBacklogOnceTheTransitionIsExecuted(): void
    {
        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $artifact  = $this->createMock(Artifact::class);
        $artifact->method('getId')->willReturn(999);
        $changeset->method('getArtifact')->willReturn($artifact);
        $tracker = TrackerTestBuilder::aTracker()->withId(10)->withProject(new \Project(['group_id' => 102]))->build();
        $artifact->method('getTracker')->willReturn($tracker);

        $this->top_backlog_change_processor->expects(self::once())->method('processTopBacklogChangeForAProgram');

        $this->getPostAction()->after($changeset);
    }

    public function testDoesNothingIfSomeReasonWeTryToProcessAnArtifactThatIsNotPartOfAProgram(): void
    {
        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $tracker   = TrackerTestBuilder::aTracker()->withId(10)->withProject(new \Project(['group_id' => 103]))->build();
        $artifact  = ArtifactTestBuilder::anArtifact(1)->inTracker($tracker)->build();
        $changeset->method('getArtifact')->willReturn($artifact);
        $this->build_program = BuildProgramStub::stubInvalidProgram();

        $this->top_backlog_change_processor->expects(self::never())->method('processTopBacklogChangeForAProgram');

        $this->getPostAction()->after($changeset);
    }

    public function testItExportsToXMl(): void
    {
        $root = new \SimpleXMLElement('<postactions></postactions>');
        $this->getPostAction()->exportToXml($root, []);

        self::assertEquals(1, $root->children()->count());
        self::assertEquals("postaction_add_to_program_top_backlog", $root->children()->getName());
    }

    private function getPostAction(): AddToTopBacklogPostAction
    {
        return new AddToTopBacklogPostAction(
            $this->createMock(\Transition::class),
            1,
            $this->build_program,
            $this->top_backlog_change_processor
        );
    }
}
