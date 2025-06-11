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

declare(strict_types=1);

namespace Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_FormElement_Field_SubmittedOn;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Semantic\Contributor\TrackerSemanticContributorDao;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusDao;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Description\SearchTrackersWithoutDescriptionSemanticStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\SearchTrackersWithoutTitleSemanticStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MetadataUsageCheckerTest extends TestCase
{
    private const FIRST_TRACKER_ID  = 101;
    private const SECOND_TRACKER_ID = 102;
    private PFUser $user;
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private SearchTrackersWithoutTitleSemanticStub $title_verifier;
    private SearchTrackersWithoutDescriptionSemanticStub $description_verifier;
    private TrackerSemanticStatusDao&MockObject $status_dao;
    private TrackerSemanticContributorDao&MockObject $assigned_to;
    private Tracker $tracker_101;
    private Tracker $tracker_102;
    private Tracker_FormElement_Field_SubmittedOn&MockObject $submitted_on_101;
    private Tracker_FormElement_Field_SubmittedOn&MockObject $submitted_on_102;

    public function setUp(): void
    {
        $this->tracker_101 = TrackerTestBuilder::aTracker()->withId(self::FIRST_TRACKER_ID)->build();
        $this->tracker_102 = TrackerTestBuilder::aTracker()->withId(self::SECOND_TRACKER_ID)->build();

        $this->submitted_on_101 = $this->createMock(Tracker_FormElement_Field_SubmittedOn::class);
        $this->submitted_on_102 = $this->createMock(Tracker_FormElement_Field_SubmittedOn::class);

        $this->form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->title_verifier       = SearchTrackersWithoutTitleSemanticStub::withAllTrackersHaveTitle();
        $this->description_verifier = SearchTrackersWithoutDescriptionSemanticStub::withAllTrackersHaveDescription();
        $this->status_dao           = $this->createMock(TrackerSemanticStatusDao::class);
        $this->assigned_to          = $this->createMock(TrackerSemanticContributorDao::class);

        $this->user = UserTestBuilder::aUser()->build();
    }

    private function checkMetadata(Metadata $metadata): void
    {
        $checker = new MetadataUsageChecker(
            $this->form_element_factory,
            $this->title_verifier,
            $this->description_verifier,
            $this->status_dao,
            $this->assigned_to
        );
        $checker->checkMetadataIsUsedByAllTrackers($metadata, [$this->tracker_101, $this->tracker_102], $this->user);
    }

    public function testItShouldRaiseAnErrorIfThereIsNotSemanticTitleInTrackers(): void
    {
        $this->title_verifier = SearchTrackersWithoutTitleSemanticStub::withTrackersThatDoNotHaveTitle(
            self::FIRST_TRACKER_ID,
            self::SECOND_TRACKER_ID
        );

        $this->expectException(TitleIsMissingInAllTrackersException::class);

        $this->checkMetadata(new Metadata('title'));
    }

    public function testItShouldRaiseNoErrorIfThereIsAtLeastOneSemanticTitleDefined(): void
    {
        $this->expectNotToPerformAssertions();

        $this->title_verifier = SearchTrackersWithoutTitleSemanticStub::withTrackersThatDoNotHaveTitle(
            self::SECOND_TRACKER_ID
        );

        $this->checkMetadata(new Metadata('title'));
    }

    public function testItShouldRaiseAnErrorIfThereIsNotSemanticDescriptionInTrackers(): void
    {
        $this->description_verifier = SearchTrackersWithoutDescriptionSemanticStub::withTrackersThatDoNotHaveDescription(
            self::FIRST_TRACKER_ID,
            self::SECOND_TRACKER_ID
        );

        $this->expectException(DescriptionIsMissingInAllTrackersException::class);

        $this->checkMetadata(new Metadata('description'));
    }

    public function testItShouldRaiseNoErrorIfThereIsAtLeastOneSemanticDescriptionDefined(): void
    {
        $this->expectNotToPerformAssertions();

        $this->description_verifier = SearchTrackersWithoutDescriptionSemanticStub::withTrackersThatDoNotHaveDescription(
            self::SECOND_TRACKER_ID
        );

        $this->checkMetadata(new Metadata('description'));
    }

    public function testItShouldRaiseAnErrorIfThereIsNotSemanticStatusInTrackers(): void
    {
        $this->status_dao->method('getNbOfTrackerWithoutSemanticStatusDefined')->willReturn(2);

        $this->expectException(StatusIsMissingInAllTrackersException::class);

        $this->checkMetadata(new Metadata('status'));
    }

    public function testItShouldRaiseNoErrorIfThereIsAtLeastOneSemanticStatusDefined(): void
    {
        $this->expectNotToPerformAssertions();

        $this->status_dao->method('getNbOfTrackerWithoutSemanticStatusDefined')->willReturn(1);

        $this->checkMetadata(new Metadata('status'));
    }

    public function testItShouldRaiseAnErrorIfThereIsNotSemanticContributorInTrackers(): void
    {
        $this->assigned_to->method('getNbOfTrackerWithoutSemanticContributorDefined')->willReturn(2);

        $this->expectException(AssignedToIsMissingInAllTrackersException::class);

        $this->checkMetadata(new Metadata('assigned_to'));
    }

    public function testItShouldRaiseNoErrorIfThereIsAtLeastOneSemanticContributorDefined(): void
    {
        $this->expectNotToPerformAssertions();

        $this->assigned_to->method('getNbOfTrackerWithoutSemanticContributorDefined')->willReturn(1);

        $this->checkMetadata(new Metadata('assigned_to'));
    }

    public function testItShouldRaiseNoErrorIfThereIsNoSubmittedOnFieldInTrackers(): void
    {
        $this->expectNotToPerformAssertions();
        $this->submitted_on_101->method('userCanRead')->willReturn(false);
        $fields_map = [
            [$this->tracker_101, 'subon', true, [$this->submitted_on_101]],
            [$this->tracker_102, 'subon', true, []],
        ];
        $this->form_element_factory->method('getFormElementsByType')->willReturnMap($fields_map);
        $this->checkMetadata(new Metadata('submitted_on'));
    }

    public function testItShouldNotRaiseAnErrorIfThereIsAtLeastOneReadableSubmittedOnField(): void
    {
        $this->expectNotToPerformAssertions();

        $this->submitted_on_101->method('userCanRead')->willReturn(false);
        $this->submitted_on_102->method('userCanRead')->willReturn(true);
        $fields_map = [
            [$this->tracker_101, 'subon', true, [$this->submitted_on_101]],
            [$this->tracker_102, 'subon', true, [$this->submitted_on_102]],
        ];
        $this->form_element_factory->method('getFormElementsByType')->willReturnMap($fields_map);

        $this->checkMetadata(new Metadata('submitted_on'));
    }

    public function testItShouldNotRaiseAnErrorIfAllSubmittedOnFieldsAreReadable(): void
    {
        $this->expectNotToPerformAssertions();

        $this->submitted_on_101->method('userCanRead')->willReturn(true);
        $this->submitted_on_102->method('userCanRead')->willReturn(true);
        $fields_map = [
            [$this->tracker_101, 'subon', true, [$this->submitted_on_101]],
            [$this->tracker_102, 'subon', true, [$this->submitted_on_102]],
        ];
        $this->form_element_factory->method('getFormElementsByType')->willReturnMap($fields_map);

        $this->checkMetadata(new Metadata('submitted_on'));
    }

    public function testProjectNameIsAlwaysGood(): void
    {
        $this->expectNotToPerformAssertions();
        $this->checkMetadata(new Metadata('project.name'));
    }

    public function testTrackerNameIsAlwaysGood(): void
    {
        $this->expectNotToPerformAssertions();
        $this->checkMetadata(new Metadata('tracker.name'));
    }

    public function testItShouldRaiseAnErrorIfSemanticTitleIsNotDefinedForPrettyTitle(): void
    {
        $this->title_verifier = SearchTrackersWithoutTitleSemanticStub::withTrackersThatDoNotHaveTitle(
            self::FIRST_TRACKER_ID,
            self::SECOND_TRACKER_ID
        );

        $this->expectException(TitleIsMissingInAllTrackersException::class);

        $this->checkMetadata(new Metadata('pretty_title'));
    }

    public function testItShouldNotRaiseAnErrorIfAllIsGoodForPrettyTitle(): void
    {
        $this->expectNotToPerformAssertions();

        $fields_map = [
            [$this->tracker_101, 'aid', true, [
                IntFieldBuilder::anIntField(1)->withReadPermission($this->user, true)->build(),
            ],
            ],
            [$this->tracker_102, 'aid', true, [
                IntFieldBuilder::anIntField(2)->withReadPermission($this->user, true)->build(),
            ],
            ],
        ];
        $this->form_element_factory->method('getFormElementsByType')->willReturnMap($fields_map);

        $this->checkMetadata(new Metadata('pretty_title'));
    }
}
