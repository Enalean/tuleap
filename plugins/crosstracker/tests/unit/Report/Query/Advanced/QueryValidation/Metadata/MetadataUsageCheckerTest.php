<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_FormElement_Field_SubmittedOn;
use Tracker_FormElementFactory;
use Tracker_Semantic_ContributorDao;
use Tracker_Semantic_DescriptionDao;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_TitleDao;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MetadataUsageCheckerTest extends TestCase
{
    /**
     * @var Tracker[]
     */
    private array $trackers;
    private PFUser $user;
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private Tracker_Semantic_TitleDao&MockObject $title_dao;
    private Tracker_Semantic_DescriptionDao&MockObject $description_dao;
    private Tracker_Semantic_StatusDao&MockObject $status_dao;
    private Tracker_Semantic_ContributorDao&MockObject $assigned_to;
    private MetadataUsageChecker $checker;
    private Tracker $tracker_101;
    private Tracker $tracker_102;
    private Tracker_FormElement_Field_SubmittedOn&MockObject $submitted_on_101;
    private Tracker_FormElement_Field_SubmittedOn&MockObject $submitted_on_102;

    public function setUp(): void
    {
        $this->tracker_101 = TrackerTestBuilder::aTracker()->withId(101)->build();
        $this->tracker_102 = TrackerTestBuilder::aTracker()->withId(102)->build();
        $this->trackers    = [$this->tracker_101, $this->tracker_102];

        $this->submitted_on_101 = $this->createMock(Tracker_FormElement_Field_SubmittedOn::class);
        $this->submitted_on_102 = $this->createMock(Tracker_FormElement_Field_SubmittedOn::class);

        $this->form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->title_dao            = $this->createMock(Tracker_Semantic_TitleDao::class);
        $this->description_dao      = $this->createMock(Tracker_Semantic_DescriptionDao::class);
        $this->status_dao           = $this->createMock(Tracker_Semantic_StatusDao::class);
        $this->assigned_to          = $this->createMock(Tracker_Semantic_ContributorDao::class);

        $this->user = UserTestBuilder::aUser()->build();

        $this->checker = new MetadataUsageChecker(
            $this->form_element_factory,
            $this->title_dao,
            $this->description_dao,
            $this->status_dao,
            $this->assigned_to
        );
    }

    public function testItShouldRaiseAnErrorIfThereIsNotSemanticTitleInTrackers(): void
    {
        $this->title_dao->method('getNbOfTrackerWithoutSemanticTitleDefined')->willReturn(2);

        self::expectException(TitleIsMissingInAllTrackersException::class);

        $metadata = new Metadata('title');
        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }

    public function testItShouldRaiseNoErrorIfThereIsAtLeastOneSemanticTitleDefined(): void
    {
        self::expectNotToPerformAssertions();

        $this->title_dao->method('getNbOfTrackerWithoutSemanticTitleDefined')->willReturn(1);

        $metadata = new Metadata('title');
        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }

    public function testItShouldRaiseAnErrorIfThereIsNotSemanticDescriptionInTrackers(): void
    {
        $this->description_dao->method('getNbOfTrackerWithoutSemanticDescriptionDefined')->willReturn(2);

        self::expectException(DescriptionIsMissingInAllTrackersException::class);

        $metadata = new Metadata('description');
        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }

    public function testItShouldRaiseNoErrorIfThereIsAtLeastOneSemanticDescriptionDefined(): void
    {
        self::expectNotToPerformAssertions();

        $this->description_dao->method('getNbOfTrackerWithoutSemanticDescriptionDefined')->willReturn(1);

        $metadata = new Metadata('description');
        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }

    public function testItShouldRaiseAnErrorIfThereIsNotSemanticStatusInTrackers(): void
    {
        $this->status_dao->method('getNbOfTrackerWithoutSemanticStatusDefined')->willReturn(2);

        self::expectException(StatusIsMissingInAllTrackersException::class);

        $metadata = new Metadata('status');
        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }

    public function testItShouldRaiseNoErrorIfThereIsAtLeastOneSemanticStatusDefined(): void
    {
        self::expectNotToPerformAssertions();

        $this->status_dao->method('getNbOfTrackerWithoutSemanticStatusDefined')->willReturn(1);

        $metadata = new Metadata('status');
        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }

    public function testItShouldRaiseAnErrorIfThereIsNotSemanticContributorInTrackers(): void
    {
        $this->assigned_to->method('getNbOfTrackerWithoutSemanticContributorDefined')->willReturn(2);

        self::expectException(AssignedToIsMissingInAllTrackersException::class);

        $metadata = new Metadata('assigned_to');
        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }

    public function testItShouldRaiseNoErrorIfThereIsAtLeastOneSemanticContributorDefined(): void
    {
        self::expectNotToPerformAssertions();

        $this->assigned_to->method('getNbOfTrackerWithoutSemanticContributorDefined')->willReturn(1);

        $metadata = new Metadata('assigned_to');
        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }

    public function testItShouldRaiseNoErrorIfThereIsNoSubmittedOnFieldInTrackers(): void
    {
        self::expectNotToPerformAssertions();
        $this->submitted_on_101->method('userCanRead')->willReturn(false);
        $fields_map = [
            [$this->tracker_101, 'subon', true, [$this->submitted_on_101]],
            [$this->tracker_102, 'subon', true, []],
        ];
        $this->form_element_factory->method('getFormElementsByType')->willReturnMap($fields_map);
        $metadata = new Metadata('submitted_on');
        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }

    public function testItShouldNotRaiseAnErrorIfThereIsAtLeastOneReadableSubmittedOnField(): void
    {
        self::expectNotToPerformAssertions();

        $this->submitted_on_101->method('userCanRead')->willReturn(false);
        $this->submitted_on_102->method('userCanRead')->willReturn(true);
        $fields_map = [
            [$this->tracker_101, 'subon', true, [$this->submitted_on_101]],
            [$this->tracker_102, 'subon', true, [$this->submitted_on_102]],
        ];
        $this->form_element_factory->method('getFormElementsByType')->willReturnMap($fields_map);

        $metadata = new Metadata('submitted_on');

        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }

    public function testItShouldNotRaiseAnErrorIfAllSubmittedOnFieldsAreReadable(): void
    {
        self::expectNotToPerformAssertions();

        $this->submitted_on_101->method('userCanRead')->willReturn(true);
        $this->submitted_on_102->method('userCanRead')->willReturn(true);
        $fields_map = [
            [$this->tracker_101, 'subon', true, [$this->submitted_on_101]],
            [$this->tracker_102, 'subon', true, [$this->submitted_on_102]],
        ];
        $this->form_element_factory->method('getFormElementsByType')->willReturnMap($fields_map);

        $metadata = new Metadata('submitted_on');

        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }

    public function testProjectNameIsAlwaysGood(): void
    {
        self::expectNotToPerformAssertions();

        $metadata = new Metadata('project.name');
        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }

    public function testTrackerNameIsAlwaysGood(): void
    {
        self::expectNotToPerformAssertions();

        $metadata = new Metadata('tracker.name');
        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }

    public function testItShouldRaiseAnErrorIfSemanticTitleIsNotDefinedForPrettyTitle(): void
    {
        $this->title_dao->method('getNbOfTrackerWithoutSemanticTitleDefined')->willReturn(2);

        self::expectException(TitleIsMissingInAllTrackersException::class);

        $metadata = new Metadata('pretty_title');
        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }

    public function testItShouldNotRaiseAnErrorIfAllIsGoodForPrettyTitle(): void
    {
        self::expectNotToPerformAssertions();

        $this->title_dao->method('getNbOfTrackerWithoutSemanticTitleDefined')->willReturn(0);

        $fields_map = [
            [$this->tracker_101, 'aid', true, [IntFieldBuilder::anIntField(1)->withReadPermission($this->user, true)->build()]],
            [$this->tracker_102, 'aid', true, [IntFieldBuilder::anIntField(2)->withReadPermission($this->user, true)->build()]],
        ];
        $this->form_element_factory->method('getFormElementsByType')->willReturnMap($fields_map);

        $metadata = new Metadata('pretty_title');
        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $this->trackers, $this->user);
    }
}
