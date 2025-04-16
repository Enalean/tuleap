<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Administration;

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use TrackerFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\TestManagement\MissingArtifactLinkException;
use Tuleap\TestManagement\TrackerDefinitionNotValidException;
use Tuleap\TestManagement\TrackerExecutionNotValidException;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Project $project;
    private TrackerChecker $tracker_checker;
    private TrackerFactory&MockObject $tracker_factory;
    private FrozenFieldsDao&MockObject $frozen_field_dao;
    private HiddenFieldsetsDao&MockObject $hidden_fieldset_dao;
    private FieldUsageDetector&MockObject $ttm_field_usage_detector;

    protected function setUp(): void
    {
        parent::setUp();

        $campaign_tracker           = TrackerTestBuilder::aTracker()->withId(1)->build();
        $definition_tracker         = TrackerTestBuilder::aTracker()->withId(2)->build();
        $execution_tracker          = TrackerTestBuilder::aTracker()->withId(3)->build();
        $issue_tracker              = TrackerTestBuilder::aTracker()->withId(4)->build();
        $tracker_from_other_project = TrackerTestBuilder::aTracker()->withId(5)->build();
        $deleted_tracker            = TrackerTestBuilder::aTracker()->withId(6)->withDeletionDate(1234567890)->build();

        $this->project = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->tracker_factory = $this->createMock(\TrackerFactory::class);
        $this->tracker_factory->method('getTrackersByGroupId')->with(101)->willReturn([
            $campaign_tracker,
            $definition_tracker,
            $execution_tracker,
            $issue_tracker,
        ]);

        $this->tracker_factory->method('getTrackerById')->willReturnCallback(static fn (int $id) => match ($id) {
            1 => $campaign_tracker,
            2 => $definition_tracker,
            3 => $execution_tracker,
            4 => $issue_tracker,
            5 => $tracker_from_other_project,
            6 => $deleted_tracker,
            default => null,
        });

        $this->frozen_field_dao         = $this->createMock(FrozenFieldsDao::class);
        $this->hidden_fieldset_dao      = $this->createMock(HiddenFieldsetsDao::class);
        $this->ttm_field_usage_detector = $this->createMock(FieldUsageDetector::class);

        $this->tracker_checker = new TrackerChecker(
            $this->tracker_factory,
            $this->frozen_field_dao,
            $this->hidden_fieldset_dao,
            $this->ttm_field_usage_detector
        );
    }

    public function testItDoesNotThrowExceptionIfProvidedTrackerIdIsInProject()
    {
        $submitted_id = 1;

        $this->ttm_field_usage_detector->method('isArtifactLinksFieldUsed')->willReturn(true);
        $this->tracker_checker->checkSubmittedTrackerCanBeUsed($this->project, $submitted_id);

        $this->addToAssertionCount(1);
    }

    public function testItDoesThrowExceptionIfProvidedTrackerIdIsInProjectButNoArtifactLinkInTracker()
    {
        $submitted_id = 1;

        $this->ttm_field_usage_detector->method('isArtifactLinksFieldUsed')->willReturn(false);
        $this->expectException(MissingArtifactLinkException::class);

        $this->tracker_checker->checkSubmittedTrackerCanBeUsed($this->project, $submitted_id);
    }

    public function testItDoesNotThrowExceptionIfProvidedDefinitionTrackerIdCanBeUsed()
    {
        $submitted_id = 1;

        $this->ttm_field_usage_detector->method('isArtifactLinksFieldUsed')->willReturn(true);

        $this->ttm_field_usage_detector->method('isStepDefinitionFieldUsed')->willReturn(true);
        $this->frozen_field_dao->method('isAFrozenFieldPostActionUsedInTracker')->with(1)->willReturn(false);
        $this->hidden_fieldset_dao->method('isAHiddenFieldsetPostActionUsedInTracker')->with(1)->willReturn(false);

        $this->tracker_checker->checkSubmittedDefinitionTrackerCanBeUsed($this->project, $submitted_id);

        $this->addToAssertionCount(1);
    }

    public function testItDoesThrowExceptionIfProvidedDefinitionHaveNoStepDefinitionField()
    {
        $submitted_id = 1;

        $this->ttm_field_usage_detector->method('isArtifactLinksFieldUsed')->willReturn(true);

        $this->ttm_field_usage_detector->method('isStepDefinitionFieldUsed')->willReturn(false);
        $this->frozen_field_dao->expects($this->never())->method('isAFrozenFieldPostActionUsedInTracker');
        $this->hidden_fieldset_dao->expects($this->never())->method('isAHiddenFieldsetPostActionUsedInTracker');

        $this->expectException(TrackerDefinitionNotValidException::class);

        $this->tracker_checker->checkSubmittedDefinitionTrackerCanBeUsed($this->project, $submitted_id);
    }

    public function testItDoesThrowExceptionIfProvidedExecutionHaveNoStepDefinitionField()
    {
        $submitted_id = 1;

        $this->ttm_field_usage_detector->method('isArtifactLinksFieldUsed')->willReturn(true);

        $this->ttm_field_usage_detector->method('isStepExecutionFieldUsed')->willReturn(false);

        $this->frozen_field_dao->expects($this->never())->method('isAFrozenFieldPostActionUsedInTracker');
        $this->hidden_fieldset_dao->expects($this->never())->method('isAHiddenFieldsetPostActionUsedInTracker');

        $this->expectException(TrackerExecutionNotValidException::class);

        $this->tracker_checker->checkSubmittedExecutionTrackerCanBeUsed($this->project, $submitted_id);
    }

    public function testItDoesNotThrowExceptionIfProvidedExecutionTrackerIdCanBeUsed()
    {
        $submitted_id = 1;

        $this->ttm_field_usage_detector->method('isArtifactLinksFieldUsed')->willReturn(true);
        $this->ttm_field_usage_detector->method('isStepExecutionFieldUsed')->willReturn(true);

        $this->frozen_field_dao->method('isAFrozenFieldPostActionUsedInTracker')->with(1)->willReturn(false);
        $this->hidden_fieldset_dao->method('isAHiddenFieldsetPostActionUsedInTracker')->with(1)->willReturn(false);

        $this->tracker_checker->checkSubmittedExecutionTrackerCanBeUsed($this->project, $submitted_id);

        $this->addToAssertionCount(1);
    }

    public function testItThrowsAnExceptionIfProvidedTrackerIdIsNotInProject()
    {
        $submitted_id = 5;

        $this->expectException(TrackerNotInProjectException::class);
        $this->tracker_checker->checkSubmittedTrackerCanBeUsed($this->project, $submitted_id);
    }

    public function testItThrowsAnExceptionIfProvidedTrackerDoesntExist()
    {
        $submitted_id = 7;

        $this->expectException(TrackerDoesntExistException::class);
        $this->tracker_checker->checkSubmittedTrackerCanBeUsed($this->project, $submitted_id);
    }

    public function testItThrowsAnExceptionIfProvidedTrackerIdIsDeleted()
    {
        $submitted_id = 6;

        $this->expectException(TrackerIsDeletedException::class);
        $this->tracker_checker->checkSubmittedTrackerCanBeUsed($this->project, $submitted_id);
    }

    public function testItThrowsAnExceptionIfProvidedTrackerHasAFrozenFieldsPostAction()
    {
        $submitted_id = 1;

        $this->ttm_field_usage_detector->method('isArtifactLinksFieldUsed')->willReturn(true);

        $this->ttm_field_usage_detector->method('isStepDefinitionFieldUsed')->willReturn(true);
        $this->frozen_field_dao->method('isAFrozenFieldPostActionUsedInTracker')->with(1)->willReturn(true);

        $this->expectException(TrackerHasAtLeastOneFrozenFieldsPostActionException::class);

        $this->tracker_checker->checkSubmittedDefinitionTrackerCanBeUsed($this->project, $submitted_id);
    }

    public function testItThrowsAnExceptionIfProvidedTrackerHasAHiddenFieldsetPostAction()
    {
        $submitted_id = 1;

        $this->ttm_field_usage_detector->method('isArtifactLinksFieldUsed')->willReturn(true);

        $this->ttm_field_usage_detector->method('isStepDefinitionFieldUsed')->willReturn(true);

        $this->frozen_field_dao->method('isAFrozenFieldPostActionUsedInTracker')->with(1)->willReturn(false);
        $this->hidden_fieldset_dao->method('isAHiddenFieldsetPostActionUsedInTracker')->with(1)->willReturn(true);

        $this->expectException(TrackerHasAtLeastOneHiddenFieldsetsPostActionException::class);

        $this->tracker_checker->checkSubmittedDefinitionTrackerCanBeUsed($this->project, $submitted_id);
    }
}
