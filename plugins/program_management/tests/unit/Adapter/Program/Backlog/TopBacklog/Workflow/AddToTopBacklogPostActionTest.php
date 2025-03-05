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

use Tracker_FormElement_Field;
use Tuleap\GlobalLanguageMock;
use Tuleap\ProgramManagement\Adapter\Workspace\ProgramServiceIsEnabledCertifier;
use Tuleap\ProgramManagement\ProgramService;
use Tuleap\ProgramManagement\Tests\Stub\Program\Backlog\TopBacklog\TopBacklogChangeProcessorStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\PostAction\Visitor;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AddToTopBacklogPostActionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private TopBacklogChangeProcessorStub $top_backlog_change_processor;

    protected function setUp(): void
    {
        $this->top_backlog_change_processor = TopBacklogChangeProcessorStub::withCallCount();
    }

    private function getPostAction(): AddToTopBacklogPostAction
    {
        return new AddToTopBacklogPostAction(
            $this->createMock(\Transition::class),
            1,
            new ProgramServiceIsEnabledCertifier(),
            $this->top_backlog_change_processor
        );
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
        $changeset = ChangesetTestBuilder::aChangeset(432)
            ->ofArtifact(
                ArtifactTestBuilder::anArtifact(999)
                    ->inTracker(
                        TrackerTestBuilder::aTracker()->withId(10)
                            ->withProject(
                                ProjectTestBuilder::aProject()->withId(102)
                                    ->withUsedService(ProgramService::SERVICE_SHORTNAME)
                                    ->build()
                            )->build()
                    )->build()
            )->build();

        $this->getPostAction()->after($changeset);

        self::assertSame(1, $this->top_backlog_change_processor->getCallCount());
    }

    public function testItDoesNothingIfForSomeReasonWeTryToProcessAnArtifactThatIsNotPartOfAProgram(): void
    {
        $changeset = ChangesetTestBuilder::aChangeset(465)
            ->ofArtifact(
                ArtifactTestBuilder::anArtifact(1)
                    ->inTracker(
                        TrackerTestBuilder::aTracker()->withId(10)
                            ->withProject(
                                ProjectTestBuilder::aProject()->withId(103)->withoutServices()->build()
                            )->build()
                    )->build()
            )->build();

        $this->getPostAction()->after($changeset);

        self::assertSame(0, $this->top_backlog_change_processor->getCallCount());
    }

    public function testItExportsToXMl(): void
    {
        $root = new \SimpleXMLElement('<postactions></postactions>');
        $this->getPostAction()->exportToXml($root, []);

        self::assertSame(1, $root->children()->count());
        self::assertSame('postaction_add_to_program_top_backlog', $root->children()->getName());
    }
}
