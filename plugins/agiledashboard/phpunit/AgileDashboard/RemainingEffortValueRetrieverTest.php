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

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker_FormElementFactory;

class RemainingEffortValueRetrieverTest extends TestCase
{
    /** @var \PFUser */
    private $user;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var RemainingEffortValueRetriever */
    private $remaining_effort_retriever;

    /** @var \Tracker_Artifact */
    private $artifact;

    /** @var \Tracker */
    private $tracker;

    /** @var \Tracker_FormElement_Field_Float */
    private $remaining_effort_field;

    public function setUp(): void
    {
        parent::setUp();
        $this->form_element_factory       = Mockery::mock(Tracker_FormElementFactory::class);
        $this->remaining_effort_retriever = new RemainingEffortValueRetriever($this->form_element_factory);
        $this->user                       = Mockery::mock(\PFUser::class);

        $this->tracker  = Mockery::mock(\Tracker::class);
        $this->artifact = Mockery::mock(\Tracker_Artifact::class);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testItReturnsTheRemainingEffortValue()
    {
        $float_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_Float::class);
        $float_value->shouldReceive('getValue')->andReturn(6.7);
        $this->setUpChangesetValue($float_value);
        $this->setUpField();

        $value = $this->remaining_effort_retriever->getRemainingEffortValue($this->user, $this->artifact);

        $this->assertEquals(6.7, $value);
    }

    public function testItReturnsNullWhenThereIsNoLastChangeset()
    {
        $this->artifact->shouldReceive('getLastChangeset')->andReturnNull();
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
        $this->form_element_factory->shouldReceive('getNumericFieldByNameForUser')->andReturn(null);

        $value = $this->remaining_effort_retriever->getRemainingEffortValue($this->user, $this->artifact);

        $this->assertEquals(null, $value);
    }

    private function setUpField()
    {
        $this->remaining_effort_field = Mockery::mock(\Tracker_FormElement_Field_Float::class);
        $this->form_element_factory->shouldReceive('getNumericFieldByNameForUser')->withArgs(
            [$this->tracker, $this->user, 'remaining_effort']
        )->andReturn(
            $this->remaining_effort_field
        );
    }

    private function setUpChangesetValue($float_value)
    {
        $changeset = Mockery::mock(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getValue')->andReturn($float_value);
        $this->artifact->shouldReceive('getLastChangeset')->andReturn($changeset);
    }
}
