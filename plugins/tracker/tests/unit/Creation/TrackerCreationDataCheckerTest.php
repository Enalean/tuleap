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

declare(strict_types=1);

namespace Tuleap\Tracker\Creation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use ReferenceManager;
use Tracker;
use TrackerDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\PendingJiraImportDao;
use Tuleap\Tracker\RetrieveTracker;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerIsInvalidException;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerCreationDataCheckerTest extends TestCase
{
    private RetrieveTracker&MockObject $tracker_factory;
    private TrackerDao&MockObject $tracker_dao;
    private TrackerCreationDataChecker $checker;
    private ReferenceManager&MockObject $reference_manager;
    private PendingJiraImportDao&MockObject $pending_jira_dao;

    protected function setUp(): void
    {
        $this->reference_manager = $this->createMock(ReferenceManager::class);
        $this->tracker_dao       = $this->createMock(TrackerDao::class);
        $this->pending_jira_dao  = $this->createMock(PendingJiraImportDao::class);
        $this->tracker_factory   = $this->createMock(RetrieveTracker::class);
        $this->checker           = new TrackerCreationDataChecker(
            $this->reference_manager,
            $this->tracker_dao,
            $this->pending_jira_dao,
            $this->tracker_factory
        );
    }

    public function testItDoesNotCheckTrackerLengthInProjectDuplicationContext(): void
    {
        $project_id  = 101;
        $public_name = 'Bugs';
        $shortname   = 'bugs_with_a_very_very_long_shortname';

        $this->tracker_dao->method('isShortNameExists')->willReturn(false);
        $this->tracker_dao->method('doesTrackerNameAlreadyExist')->willReturn(false);
        $this->pending_jira_dao->method('doesTrackerNameExist')->willReturn(false);
        $this->pending_jira_dao->method('doesTrackerShortNameExist')->willReturn(false);
        $this->reference_manager->method('_isKeywordExists')->willReturn(false);

        $this->checker->checkAtProjectCreation(
            $project_id,
            $public_name,
            $shortname
        );

        $this->addToAssertionCount(1);
    }

    public function testItThrowAnExceptionWhenNewTrackerLengthIsInvalidDuringTrackerDuplication(): void
    {
        $shortname   = 'bugs_with_a_very_very_long_shortname';
        $template_id = '25';
        $user        = UserTestBuilder::buildWithDefaults();

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
        $shortname   = 'bugs';
        $template_id = '12';
        $user        = UserTestBuilder::buildWithDefaults();

        $this->tracker_factory->method('getTrackerById')->willReturn(null);

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
        $shortname   = 'bugs';
        $template_id = '12';
        $user        = UserTestBuilder::buildWithDefaults();

        $tracker = $this->createMock(Tracker::class);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);
        $project = ProjectTestBuilder::aProject()->build();
        $tracker->method('userIsAdmin')->willReturn(false);
        $tracker->method('getProject')->willReturn($project);

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
        $shortname   = 'bugs';
        $template_id = '12';
        $user        = UserTestBuilder::buildWithDefaults();

        $tracker = $this->createMock(Tracker::class);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);
        $project = ProjectTestBuilder::aProject()->withTypeTemplate()->build();
        $tracker->expects(self::never())->method('userIsAdmin');
        $tracker->method('getProject')->willReturn($project);

        $this->checker->checkAtTrackerDuplication(
            $shortname,
            $template_id,
            $user
        );
    }

    public function testItDoesNotThrowAnExceptionWhenOldTrackerLengthWasInvalid(): void
    {
        $project_id  = 101;
        $public_name = 'New bugs';
        $shortname   = 'new_bugs';

        $this->tracker_dao->method('isShortNameExists')->willReturn(false);
        $this->tracker_dao->method('doesTrackerNameAlreadyExist')->willReturn(false);
        $this->pending_jira_dao->method('doesTrackerNameExist')->willReturn(false);
        $this->pending_jira_dao->method('doesTrackerShortNameExist')->willReturn(false);

        $this->reference_manager->method('_isKeywordExists')->willReturn(false);
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
        $public_name = '';
        $shortname   = 'bugs';

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
        $public_name = 'Bugs';
        $shortname   = '';
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
        $public_name = 'New bugs';
        $shortname   = 'bugs';

        $this->tracker_dao->method('doesTrackerNameAlreadyExist')->willReturn(true);
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
        $public_name = 'New bugs';
        $shortname   = '+++bugs+++';

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
        $public_name = 'New bugs';
        $shortname   = 'new_bugs';

        $this->tracker_dao->method('isShortNameExists')->willReturn(true);
        $this->tracker_dao->method('doesTrackerNameAlreadyExist')->willReturn(false);
        $this->pending_jira_dao->method('doesTrackerNameExist')->willReturn(false);
        $this->pending_jira_dao->method('doesTrackerShortNameExist')->willReturn(false);
        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('The tracker short name new_bugs is already used. Please use another one.');

        $this->checker->checkAtProjectCreation(
            $project_id,
            $public_name,
            $shortname
        );
    }

    public function testItThrowsAnExceptionIfShortNameAlreadyExistsInPendingJiraImport(): void
    {
        $project_id  = 101;
        $public_name = 'New bugs';
        $shortname   = 'new_bugs';

        $this->tracker_dao->method('isShortNameExists')->willReturn(false);
        $this->tracker_dao->method('doesTrackerNameAlreadyExist')->willReturn(false);
        $this->pending_jira_dao->method('doesTrackerNameExist')->willReturn(false);
        $this->pending_jira_dao->method('doesTrackerShortNameExist')->willReturn(true);
        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('The tracker short name new_bugs is already used. Please use another one.');

        $this->checker->checkAtProjectCreation(
            $project_id,
            $public_name,
            $shortname
        );
    }

    public function testItThrowsAnExceptionIfNameAlreadyExists(): void
    {
        $project_id  = 101;
        $public_name = 'New bugs';
        $shortname   = 'new_bugs';

        $this->tracker_dao->method('isShortNameExists')->willReturn(false);
        $this->tracker_dao->method('doesTrackerNameAlreadyExist')->willReturn(true);
        $this->pending_jira_dao->method('doesTrackerNameExist')->willReturn(false);
        $this->pending_jira_dao->method('doesTrackerShortNameExist')->willReturn(false);
        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('The tracker name New bugs is already used. Please use another one.');

        $this->checker->checkAtProjectCreation(
            $project_id,
            $public_name,
            $shortname
        );
    }

    public function testItThrowsAnExceptionIfNameAlreadyExistsInPendingJiraImport(): void
    {
        $project_id  = 101;
        $public_name = 'New bugs';
        $shortname   = 'new_bugs';

        $this->tracker_dao->method('isShortNameExists')->willReturn(false);
        $this->tracker_dao->method('doesTrackerNameAlreadyExist')->willReturn(false);
        $this->pending_jira_dao->method('doesTrackerNameExist')->willReturn(true);
        $this->pending_jira_dao->method('doesTrackerShortNameExist')->willReturn(false);
        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('The tracker name New bugs is already used. Please use another one.');

        $this->checker->checkAtProjectCreation(
            $project_id,
            $public_name,
            $shortname
        );
    }

    public function testItThrowsAnExceptionWhenReferenceKeywordAlreadyExists(): void
    {
        $project_id  = 101;
        $public_name = 'New bugs';
        $shortname   = 'new_bugs';

        $this->tracker_dao->method('isShortNameExists')->willReturn(false);
        $this->tracker_dao->method('doesTrackerNameAlreadyExist')->willReturn(false);
        $this->pending_jira_dao->method('doesTrackerNameExist')->willReturn(false);
        $this->pending_jira_dao->method('doesTrackerShortNameExist')->willReturn(false);

        $this->reference_manager->method('_isKeywordExists')->willReturn(true);
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
        $template_id = 101;

        $this->tracker_factory->method('getTrackerById')->willReturn(null);

        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('Invalid tracker template.');

        $this->checker->checkAndRetrieveTrackerTemplate(
            $template_id
        );
    }

    public function testItThrowsAnExceptionWhenTemplateTrackerProjectIsInvalid(): void
    {
        $template_id = 101;

        $project = ProjectTestBuilder::aProject()->withError()->build();
        $tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();

        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);

        $this->expectException(TrackerIsInvalidException::class);
        $this->expectExceptionMessage('Invalid project template.');

        $this->checker->checkAndRetrieveTrackerTemplate(
            $template_id
        );
    }

    #[DataProvider('getShortNamesAndCorrespondingConversions')]
    public function testItConvertsGivenStringToValidShortName(string $expected, string $wished): void
    {
        self::assertEquals($expected, TrackerCreationDataChecker::getShortNameWithValidFormat($wished));
    }

    public static function getShortNamesAndCorrespondingConversions(): array
    {
        return [
            ['bug', 'bug'],
            ['sub_task', 'sub-task'],
            ['tache', 'tâche'],
            ['sous_tache', 'Sous-Tâche'],
            ['une_tache', 'une.tache'],
        ];
    }
}
