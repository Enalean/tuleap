<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Tracker\Creation;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TrackerFactory;
use Tuleap\Tracker\TrackerIsInvalidException;

final class TrackerCreationDataCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\TrackerDao
     */
    private $tracker_dao;
    /**
     * @var TrackerCreationDataChecker
     */
    private $checker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\ReferenceManager
     */
    private $reference_manager;

    protected function setUp(): void
    {
        $this->reference_manager = \Mockery::mock(\ReferenceManager::class);
        $this->tracker_dao       = \Mockery::mock(\TrackerDao::class);
        $this->tracker_factory   = \Mockery::mock(\TrackerFactory::class);
        $this->checker           = new TrackerCreationDataChecker($this->reference_manager, $this->tracker_dao, $this->tracker_factory);
        $this->tracker_factory   = \Mockery::mock(TrackerFactory::class);
        $this->checker           = new TrackerCreationDataChecker(
            $this->reference_manager,
            $this->tracker_dao,
            $this->tracker_factory
        );
    }

    public function testItDoesNotCheckTrackerLengthInProjectDuplicationContext(): void
    {
        $project_id  = 101;
        $public_name = "Bugs";
        $shortname   = "bugs_with_a_very_very_long_shortname";

        $this->tracker_dao->shouldReceive('isShortNameExists')->andReturn(false);
        $this->tracker_dao->shouldReceive('doesTrackerNameAlreadyExist')->andReturn(false);
        $this->reference_manager->shouldReceive('_isKeywordExists')->andReturn(false);

        $this->checker->checkAtProjectCreation(
            $project_id,
            $public_name,
            $shortname
        );

        $this->addToAssertionCount(1);
    }

    public function testItThrowAnExceptionWhenNewTrackerLengthIsInvalidDuringTrackerDuplication(): void
    {
        $shortname = "bugs_with_a_very_very_long_shortname";
        $template_id = "25";
        $user = \Mockery::mock(\PFUser::class);

        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('Tracker shortname length must be inferior to 25 characters.');
        $this->checker->checkAtTrackerDuplication(
            $shortname,
            $template_id,
            $user
        );
    }

    public function testItThrowAnExceptionWhenOriginalTrackerIsNotFound(): void
    {
        $shortname = "bugs";
        $template_id = "12";
        $user = \Mockery::mock(\PFUser::class);

        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn(null);

        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('The template id 12 used for tracker creation was not found.');
        $this->checker->checkAtTrackerDuplication(
            $shortname,
            $template_id,
            $user
        );
    }

    public function testItThrowAnExceptionWhenUserCanNotReadOriginalTrackerDuringTrackerDuplication(): void
    {
        $shortname = "bugs";
        $template_id = "12";
        $user = \Mockery::mock(\PFUser::class);

        $tracker = \Mockery::mock(\Tracker::class);
        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn($tracker);
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('isTemplate')->andReturnFalse();
        $tracker->shouldReceive('userIsAdmin')->andReturnFalse();
        $tracker->shouldReceive('getProject')->andReturn($project);

        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('The template id 12 used for tracker creation was not found.');
        $this->checker->checkAtTrackerDuplication(
            $shortname,
            $template_id,
            $user
        );
    }

    public function testItDoesNotCheckUserPermissionsWhenTrackerComeFromAProjectTemplate(): void
    {
        $shortname = "bugs";
        $template_id = "12";
        $user = \Mockery::mock(\PFUser::class);

        $tracker = \Mockery::mock(\Tracker::class);
        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn($tracker);
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('isTemplate')->andReturnTrue();
        $tracker->shouldReceive('userIsAdmin')->never();
        $tracker->shouldReceive('getProject')->andReturn($project);

        $this->checker->checkAtTrackerDuplication(
            $shortname,
            $template_id,
            $user
        );
    }

    public function testItDoesNotThrowAnExceptionWhenOldTrackerLengthWasInvalid(): void
    {
        $project_id = 101;
        $public_name = "New bugs";
        $shortname = "new_bugs";

        $this->tracker_dao->shouldReceive('isShortNameExists')->andReturn(false);
        $this->tracker_dao->shouldReceive('doesTrackerNameAlreadyExist')->andReturn(false);

        $this->reference_manager->shouldReceive('_isKeywordExists')->andReturn(false);
        $this->checker->checkAtProjectCreation(
            $project_id,
            $public_name,
            $shortname
        );
        $this->addToAssertionCount(1);
    }

    public function testItThrowsAnExceptionWhenPublicNameIsNotProvided(): void
    {
        $project_id  = 101;
        $public_name = "";
        $shortname   = "bugs";

        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('Name, color, and short name are required.');
        $this->checker->checkAtProjectCreation(
            $project_id,
            $public_name,
            $shortname
        );
    }

    public function testItThrowsAnExceptionWhenShortNameIsNotProvided(): void
    {
        $project_id  = 101;
        $public_name = "Bugs";
        $shortname   = "";
        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('Name, color, and short name are required.');
        $this->checker->checkAtProjectCreation(
            $project_id,
            $public_name,
            $shortname
        );
    }

    public function testItThrowsAnExceptionIfPublicNameAlreadyExists(): void
    {
        $project_id  = 101;
        $public_name = "New bugs";
        $shortname   = "bugs";

        $this->tracker_dao->shouldReceive('doesTrackerNameAlreadyExist')->andReturn(true);
        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('The tracker name New bugs is already used. Please use another one.');

        $this->checker->checkAtProjectCreation(
            $project_id,
            $public_name,
            $shortname
        );
    }

    public function testItThrowsAnExceptionWhenShortNameIsInvalid(): void
    {
        $project_id  = 101;
        $public_name = "New bugs";
        $shortname   = "+++bugs+++";

        $this->tracker_dao->shouldReceive('isNameExists')->andReturn(false);
        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage(
            'Invalid short name: +++bugs+++. Please use only alphanumerical characters or an unreserved reference.'
        );

        $this->checker->checkAtProjectCreation(
            $project_id,
            $public_name,
            $shortname
        );
    }

    public function testItThrowsAnExceptionIfShortNameAlreadyExists(): void
    {
        $project_id  = 101;
        $public_name = "New bugs";
        $shortname   = "new_bugs";

        $this->tracker_dao->shouldReceive('isShortNameExists')->andReturn(true);
        $this->tracker_dao->shouldReceive('doesTrackerNameAlreadyExist')->andReturn(false);
        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('The tracker short name new_bugs is already used. Please use another one.');

        $this->checker->checkAtProjectCreation(
            $project_id,
            $public_name,
            $shortname
        );
    }

    public function testItThrowsAnExceptionWhenReferenceKeywordAlreadyExists(): void
    {
        $project_id  = 101;
        $public_name = "New bugs";
        $shortname   = "new_bugs";

        $this->tracker_dao->shouldReceive('isShortNameExists')->andReturn(false);
        $this->tracker_dao->shouldReceive('doesTrackerNameAlreadyExist')->andReturn(false);

        $this->reference_manager->shouldReceive('_isKeywordExists')->andReturn(true);
        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('The tracker short name new_bugs is already used. Please use another one.');

        $this->checker->checkAtProjectCreation(
            $project_id,
            $public_name,
            $shortname
        );
    }

    public function testItThrowsAnExceptionWhenTemplateTrackerIsInvalid(): void
    {
        $template_id  = 101;

        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn(false);

        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('Invalid tracker template.');

        $this->checker->checkAndRetrieveTrackerTemplate(
            $template_id
        );
    }

    public function testItThrowsAnExceptionWhenTemplateTrackerProjectIsInvalid(): void
    {
        $template_id  = 101;

        $tracker = Mockery::mock(\Tracker::class);
        $project    = Mockery::mock(\Project::class);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $project->shouldReceive('isError')->andReturnTrue();

        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn($tracker);

        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('Invalid project template.');

        $this->checker->checkAndRetrieveTrackerTemplate(
            $template_id
        );
    }
}
