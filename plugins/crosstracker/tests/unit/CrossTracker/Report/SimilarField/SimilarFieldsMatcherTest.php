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

namespace Tuleap\CrossTracker\Report\SimilarField;

use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\Test\Builders\UserTestBuilder;

final class SimilarFieldsMatcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var SupportedFieldsDao&\PHPUnit\Framework\MockObject\MockObject */
    private $similar_fields_dao;
    /** @var \Tracker_FormElementFactory&\PHPUnit\Framework\MockObject\MockObject */
    private $form_element_factory;
    private SimilarFieldsMatcher $matcher;
    private \PFUser $user;
    /** @var CrossTrackerReport&\PHPUnit\Framework\MockObject\MockObject */
    private $report;
    /** @var SimilarFieldsFilter&\PHPUnit\Framework\MockObject\MockObject */
    private $similar_fields_filter;
    /** @var BindNameVisitor&\PHPUnit\Framework\MockObject\MockObject */
    private $bind_name_visitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->similar_fields_dao    = $this->createMock(SupportedFieldsDao::class);
        $this->form_element_factory  = $this->createMock(\Tracker_FormElementFactory::class);
        $this->report                = $this->createMock(CrossTrackerReport::class);
        $this->user                  = UserTestBuilder::aUser()->build();
        $this->similar_fields_filter = $this->createMock(SimilarFieldsFilter::class);

        $this->similar_fields_filter
            ->method('filterCandidatesUsedInSemantics')
            ->willReturnCallback(
                function (SimilarFieldCandidate ...$args): array {
                    return $args;
                }
            );

        $this->bind_name_visitor = $this->createMock(BindNameVisitor::class);

        $this->matcher = new SimilarFieldsMatcher(
            $this->similar_fields_dao,
            $this->form_element_factory,
            $this->similar_fields_filter,
            $this->bind_name_visitor
        );
    }

    public function testMatchingFieldsAreRetrieved(): void
    {
        $this->report->method('getTrackerIds')->willReturn([91, 26]);
        $first_field_row  = ['formElement_type' => 'string'];
        $second_field_row = ['formElement_type' => 'string'];
        $this->similar_fields_dao->method('searchByTrackerIds')
            ->willReturn(
                [
                    $first_field_row,
                    $second_field_row,
                ]
            );

        $first_field = $this->createMock(\Tracker_FormElement_Field::class);
        $first_field->method('getName')->willReturn('field_name');
        $first_field->method('userCanRead')->willReturn(true);
        $second_field = $this->createMock(\Tracker_FormElement_Field::class);
        $second_field->method('getName')->willReturn('field_name');
        $second_field->method('userCanRead')->willReturn(true);
        $this->form_element_factory->method('getCachedInstanceFromRow')
            ->willReturn($first_field, $second_field);

        self::assertCount(2, $this->matcher->getSimilarFieldsCollection($this->report, $this->user));
    }

    public function testMatchingFieldsWithoutEnoughPermissionsAreLeftOut(): void
    {
        $this->report->method('getTrackerIds')->willReturn([91, 26]);
        $first_field_row  = ['formElement_type' => 'string'];
        $second_field_row = ['formElement_type' => 'string'];
        $this->similar_fields_dao->method('searchByTrackerIds')
            ->willReturn(
                [
                    $first_field_row,
                    $second_field_row,
                ]
            );

        $first_field = $this->createMock(\Tracker_FormElement_Field::class);
        $first_field->method('getName')->willReturn('field_name');
        $first_field->method('userCanRead')->willReturn(true);
        $second_field = $this->createMock(\Tracker_FormElement_Field::class);
        $second_field->method('getName')->willReturn('field_name');
        $second_field->method('userCanRead')->willReturn(false);
        $this->form_element_factory->method('getCachedInstanceFromRow')
            ->willReturn($first_field, $second_field);

        self::assertCount(0, $this->matcher->getSimilarFieldsCollection($this->report, $this->user));
    }
}
