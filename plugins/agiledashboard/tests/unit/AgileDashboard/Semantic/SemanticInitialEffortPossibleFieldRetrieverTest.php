<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use PHPUnit\Framework\MockObject\Stub;
use Tracker_FormElementFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

class SemanticInitialEffortPossibleFieldRetrieverTest extends TestCase
{
    private Stub|Tracker_FormElementFactory $form_element_factory;
    private \Tracker $tracker;
    private \Tracker_FormElement_Field_Integer $field_1;
    private \Tracker_FormElement_Field_Integer $field_2;
    private \Tracker_FormElement_Field_Integer $field_remaining_effort;
    private SemanticInitialEffortPossibleFieldRetriever $possible_fields_retriever;

    protected function setUp(): void
    {
        $this->form_element_factory      = $this->createStub(Tracker_FormElementFactory::class);
        $this->form_element_factory      = $this->createStub(Tracker_FormElementFactory::class);
        $this->tracker                   = TrackerTestBuilder::aTracker()->withId(974)->build();
        $this->possible_fields_retriever = new SemanticInitialEffortPossibleFieldRetriever($this->form_element_factory);

        $this->field_1 = new \Tracker_FormElement_Field_Integer(
            564,
            974,
            '',
            "int_field",
            "int field",
            null,
            null,
            null,
            null,
            null,
            null
        );
        $this->field_2 = new \Tracker_FormElement_Field_Integer(
            565,
            974,
            '',
            "int_field_2",
            "int field 2",
            null,
            null,
            null,
            null,
            null,
            null
        );

        $this->field_remaining_effort = new \Tracker_FormElement_Field_Integer(
            567,
            974,
            '',
            "remaining_effort",
            "Remaining Effort",
            null,
            null,
            null,
            null,
            null,
            null
        );
    }

    public function testGetPossibleFieldsForInitialEffortReturnFieldsWithoutRemainingEffort(): void
    {
        $expected_result = [$this->field_1, $this->field_2];
        $this->form_element_factory->method("getUsedPotentiallyContainingNumericValueFields")->willReturn(
            [$this->field_1, $this->field_2, $this->field_remaining_effort]
        );

        $this->assertSame(
            $expected_result,
            $this->possible_fields_retriever->getPossibleFieldsForInitialEffort($this->tracker, 0)
        );
    }

    public function testGetPossibleFieldsForInitialEffortReturnRemainingEffortIfTheFieldIsSelected(): void
    {
        $expected_result = [$this->field_1, $this->field_2, $this->field_remaining_effort];
        $this->form_element_factory->method("getUsedPotentiallyContainingNumericValueFields")->willReturn(
            [$this->field_1, $this->field_2, $this->field_remaining_effort]
        );

        $this->assertSame(
            $expected_result,
            $this->possible_fields_retriever->getPossibleFieldsForInitialEffort($this->tracker, 567)
        );
    }
}
