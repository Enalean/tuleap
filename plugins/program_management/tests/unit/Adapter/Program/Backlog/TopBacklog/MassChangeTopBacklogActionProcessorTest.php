<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MassChangeTopBacklogActionProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TopBacklogChangeProcessor
     */
    private $top_backlog_change_processor;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->top_backlog_change_processor = $this->createMock(TopBacklogChangeProcessor::class);
        $this->user                         = UserTestBuilder::buildWithDefaults();
    }

    public function testCanProcessMassAdditionToTheTopBacklog(): void
    {
        $source_information = new MassChangeTopBacklogSourceInformation(102, [400, 401], $this->user, 'add');

        $expected_top_backlog_change = new TopBacklogChange([400, 401], [], false, null);

        $this->top_backlog_change_processor
            ->expects($this->once())
            ->method('processTopBacklogChangeForAProgram')
            ->with(
                self::anything(),
                $this->callback(function (
                    TopBacklogChange $top_backlog_change,
                ) use (
                    $expected_top_backlog_change
                ): bool {
                    self::assertEquals($expected_top_backlog_change, $top_backlog_change);
                    return true;
                }),
                self::anything()
            );

        $mass_change_processor = new MassChangeTopBacklogActionProcessor(
            BuildProgramStub::stubValidProgram(),
            $this->top_backlog_change_processor
        );

        $mass_change_processor->processMassChangeAction($source_information);
    }

    public function testCanProcessMassDeletionToTheTopBacklog(): void
    {
        $source_information = new MassChangeTopBacklogSourceInformation(102, [402, 403], $this->user, 'remove');

        $expected_top_backlog_change = new TopBacklogChange([], [402, 403], false, null);

        $this->top_backlog_change_processor->method('processTopBacklogChangeForAProgram')
            ->with(
                self::anything(),
                $this->callback(function (
                    TopBacklogChange $top_backlog_change,
                ) use (
                    $expected_top_backlog_change
                ): bool {
                    self::assertEquals($expected_top_backlog_change, $top_backlog_change);
                    return true;
                }),
                self::anything()
            );

        $mass_change_processor = new MassChangeTopBacklogActionProcessor(
            BuildProgramStub::stubValidProgram(),
            $this->top_backlog_change_processor
        );

        $mass_change_processor->processMassChangeAction($source_information);
    }

    public function testDoesNothingWhenProcessingAMassChangeWithNoTopBacklogModification(): void
    {
        $source_information = new MassChangeTopBacklogSourceInformation(102, [405], $this->user, 'unchanged');

        $this->top_backlog_change_processor->expects(self::never())->method('processTopBacklogChangeForAProgram');

        $mass_change_processor = new MassChangeTopBacklogActionProcessor(
            BuildProgramStub::stubValidProgram(),
            $this->top_backlog_change_processor
        );

        $mass_change_processor->processMassChangeAction($source_information);
    }

    public function testDoesNothingWhenAMassChangeHappensOutsideOfAProgramProject(): void
    {
        $source_information = new MassChangeTopBacklogSourceInformation(200, [406], $this->user, 'add');

        $this->top_backlog_change_processor->expects(self::never())->method('processTopBacklogChangeForAProgram');

        $mass_change_processor = new MassChangeTopBacklogActionProcessor(
            BuildProgramStub::stubInvalidProgram(),
            $this->top_backlog_change_processor
        );

        $mass_change_processor->processMassChangeAction($source_information);
    }
}
