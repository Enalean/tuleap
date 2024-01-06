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

namespace Tuleap\AgileDashboard;

require_once __DIR__ . '/../bootstrap.php';

use Tracker_FormElementFactory;

class RemainingEffortValueRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var \PFUser */
    private $user;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var RemainingEffortValueRetriever */
    private $remaining_effort_retriever;

    /** @var \Tuleap\Tracker\Artifact\Artifact */
    private $artifact;

    /** @var \Tracker */
    private $tracker;

    /** @var \Tracker_FormElement_Field_Float */
    private $remaining_effort_field;

    public function setUp(): void
    {
        parent::setUp();
        $this->form_element_factory       = $this->createMock(Tracker_FormElementFactory::class);
        $this->remaining_effort_retriever = new RemainingEffortValueRetriever($this->form_element_factory);
        $this->user                       = $this->createMock(\PFUser::class);

        $this->tracker  = $this->createMock(\Tracker::class);
        $this->artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->method('getTracker')->willReturn($this->tracker);
    }

    public function testItReturnsTheRemainingEffortValue()
    {
        $float_value = $this->createMock(\Tracker_Artifact_ChangesetValue_Float::class);
        $float_value->method('getValue')->willReturn(6.7);
        $this->setUpChangesetValue($float_value);
        $this->setUpField();

        $value = $this->remaining_effort_retriever->getRemainingEffortValue($this->user, $this->artifact);

        $this->assertEquals(6.7, $value);
    }

    public function testItReturnsNullWhenThereIsNoLastChangeset()
    {
        $this->artifact->method('getLastChangeset')->willReturn(null);
        $this->setUpField();

        $value = $this->remaining_effort_retriever->getRemainingEffortValue($this->user, $this->artifact);

        $this->assertEquals(null, $value);
    }

    public function testItReturnsNullWhenThereIsNoValue()
    {
        $this->setUpChangesetValue(null);
        $this->setUpField();

        $value = $this->remaining_effort_retriever->getRemainingEffortValue($this->user, $this->artifact);

        $this->assertEquals(null, $value);
    }

    public function testItReturnsNullWhenThereIsNoField()
    {
        $this->form_element_factory->method('getNumericFieldByNameForUser')->willReturn(null);

        $value = $this->remaining_effort_retriever->getRemainingEffortValue($this->user, $this->artifact);

        $this->assertEquals(null, $value);
    }

    private function setUpField()
    {
        $this->remaining_effort_field = $this->createMock(\Tracker_FormElement_Field_Float::class);
        $this->form_element_factory->method('getNumericFieldByNameForUser')->with(
            $this->tracker,
            $this->user,
            'remaining_effort',
        )->willReturn(
            $this->remaining_effort_field
        );
    }

    private function setUpChangesetValue($float_value)
    {
        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $changeset->method('getValue')->willReturn($float_value);
        $this->artifact->method('getLastChangeset')->willReturn($changeset);
    }
}
