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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Program;
use Tuleap\Test\Builders\UserTestBuilder;

final class MassChangeTopBacklogActionProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BuildProgram
     */
    private $build_program;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TopBacklogChangeProcessor
     */
    private $top_backlog_change_processor;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker
     */
    private $tracker;
    /**
     * @var MassChangeTopBacklogActionProcessor
     */
    private $mass_change_processor;

    protected function setUp(): void
    {
        $this->build_program                = \Mockery::mock(BuildProgram::class);
        $this->top_backlog_change_processor = \Mockery::mock(TopBacklogChangeProcessor::class);
        $this->tracker                      = \Mockery::mock(\Tracker::class);
        $this->tracker->shouldReceive('getGroupId')->andReturn('102');

        $this->mass_change_processor = new MassChangeTopBacklogActionProcessor(
            $this->build_program,
            $this->top_backlog_change_processor
        );
    }

    public function testCanProcessMassAdditionToTheTopBacklog(): void
    {
        $this->build_program->shouldReceive('buildExistingProgramProject')->andReturn(new Program(102));
        $source_information = new MassChangeTopBacklogSourceInformation(102, [400, 401], UserTestBuilder::aUser()->build(), 'add');

        $expected_top_backlog_change = new TopBacklogChange([400, 401], [], false, null);

        $this->top_backlog_change_processor->shouldReceive('processTopBacklogChangeForAProgram')
            ->withArgs(function (
                Program $program,
                TopBacklogChange $top_backlog_change,
                \PFUser $user
            ) use (
                $expected_top_backlog_change
            ): bool {
                self::assertEquals($expected_top_backlog_change, $top_backlog_change);
                return true;
            })->once();

        $this->mass_change_processor->processMassChangeAction($source_information);
    }

    public function testCanProcessMassDeletionToTheTopBacklog(): void
    {
        $this->build_program->shouldReceive('buildExistingProgramProject')->andReturn(new Program(102));
        $source_information = new MassChangeTopBacklogSourceInformation(102, [402, 403], UserTestBuilder::aUser()->build(), 'remove');

        $expected_top_backlog_change = new TopBacklogChange([], [402, 403], false, null);

        $this->top_backlog_change_processor->shouldReceive('processTopBacklogChangeForAProgram')
            ->withArgs(function (
                Program $program,
                TopBacklogChange $top_backlog_change,
                \PFUser $user
            ) use (
                $expected_top_backlog_change
            ): bool {
                self::assertEquals($expected_top_backlog_change, $top_backlog_change);
                return true;
            })->once();

        $this->mass_change_processor->processMassChangeAction($source_information);
    }

    public function testDoesNothingWhenProcessingAMassChangeWithNoTopBacklogModification(): void
    {
        $this->build_program->shouldReceive('buildExistingProgramProject')->andReturn(new Program(102));
        $source_information = new MassChangeTopBacklogSourceInformation(102, [405], UserTestBuilder::aUser()->build(), 'unchanged');

        $this->top_backlog_change_processor->shouldNotReceive('processTopBacklogChangeForAProgram');

        $this->mass_change_processor->processMassChangeAction($source_information);
    }

    public function testDoesNothingWhenAMassChangeHappensOutsideOfAProgramProject(): void
    {
        $this->build_program->shouldReceive('buildExistingProgramProject')->andThrow(new ProjectIsNotAProgramException(200));
        $source_information = new MassChangeTopBacklogSourceInformation(200, [406], UserTestBuilder::aUser()->build(), 'add');

        $this->top_backlog_change_processor->shouldNotReceive('processTopBacklogChangeForAProgram');

        $this->mass_change_processor->processMassChangeAction($source_information);
    }
}
