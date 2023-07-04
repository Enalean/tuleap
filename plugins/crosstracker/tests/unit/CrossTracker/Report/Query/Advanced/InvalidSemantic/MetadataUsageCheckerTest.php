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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_FormElement_Field_SubmittedOn;
use Tracker_FormElementFactory;
use Tracker_Semantic_ContributorDao;
use Tracker_Semantic_DescriptionDao;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_TitleDao;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidComparisonCollectorParameters;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\MetadataUsageChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\SubmittedOnIsMissingInAtLeastOneTrackerException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSearchablesCollection;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class MetadataUsageCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var Tracker_Semantic_ContributorDao&MockObject
     */
    private $assigned_to;
    /**
     * @var Tracker[]
     */
    private array $trackers;
    private PFUser $user;
    /**
     * @var Tracker_FormElementFactory&MockObject
     */
    private $form_element_factory;
    /**
     * @var Tracker_Semantic_TitleDao&MockObject
     */
    private $title_dao;
    /**
     * @var Tracker_Semantic_DescriptionDao&MockObject
     */
    private $description_dao;
    /**
     * @var Tracker_Semantic_StatusDao&MockObject
     */
    private $status_dao;
    private MetadataUsageChecker $checker;
    private Tracker $tracker_101;
    private Tracker $tracker_102;
    /**
     * @var InvalidSearchablesCollection
     */
    private $invalid_searchable_collection;
    /**
     * @var Tracker_FormElement_Field_SubmittedOn&MockObject
     */
    private $submitted_on_101;
    /**
     * @var Tracker_FormElement_Field_SubmittedOn&MockObject
     */
    private $submitted_on_102;

    public function setUp(): void
    {
        $this->invalid_searchable_collection = $this->createMock(InvalidSearchablesCollection::class);

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

    public function testItShouldRaiseAnErrorIfThereIsNoSubmittedOnFieldOnAtLeastOneTracker(): void
    {
        $this->submitted_on_101->method('userCanRead')->willReturn(true);
        $fields_map = [
            [$this->tracker_101, 'subon', true, [$this->submitted_on_101]],
            [$this->tracker_102, 'subon', true, []],
        ];
        $this->form_element_factory->method('getFormElementsByType')->willReturnMap($fields_map);

        $metadata   = new Metadata('submitted_on');
        $parameters = new InvalidComparisonCollectorParameters(
            $this->invalid_searchable_collection,
            $this->trackers,
            $this->user
        );

        $this->expectException(SubmittedOnIsMissingInAtLeastOneTrackerException::class);

        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $parameters);
    }

    public function testItShouldRaiseAnErrorIfThereIsNoReadableSubmittedOnField(): void
    {
        $this->submitted_on_101->method('userCanRead')->willReturn(false);
        $this->submitted_on_102->method('userCanRead')->willReturn(true);
        $fields_map = [
            [$this->tracker_101, 'subon', true, [$this->submitted_on_101]],
            [$this->tracker_102, 'subon', true, [$this->submitted_on_102]],
        ];
        $this->form_element_factory->method('getFormElementsByType')->willReturnMap($fields_map);

        $metadata   = new Metadata('submitted_on');
        $parameters = new InvalidComparisonCollectorParameters(
            $this->invalid_searchable_collection,
            $this->trackers,
            $this->user
        );

        $this->expectException(SubmittedOnIsMissingInAtLeastOneTrackerException::class);

        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $parameters);
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

        $metadata   = new Metadata('submitted_on');
        $parameters = new InvalidComparisonCollectorParameters(
            $this->invalid_searchable_collection,
            $this->trackers,
            $this->user
        );

        $this->checker->checkMetadataIsUsedByAllTrackers($metadata, $parameters);
    }
}
