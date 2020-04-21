<?php
/**
 * Copyright (c) Enalean, 2015-present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class Tracker_Artifact_ChangesetValue_DateTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $changeset;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Date
     */
    private $field;

    protected function setUp(): void
    {
        parent::setUp();

        $this->field = Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $this->changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
    }

    public function testDates(): void
    {
        $this->field->shouldReceive('formatDateForDisplay')->withArgs([1221221466])->andReturn("12/09/2008");
        $date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221466);
        $this->assertEquals(1221221466, $date->getTimestamp());
        $this->assertEquals("12/09/2008", $date->getDate());

        $this->field->shouldReceive('formatDateForDisplay')->withArgs([1221221467])->andReturn("2008-09-12");
        $date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221467);
        $this->assertEquals(1221221467, $date->getTimestamp());
        $this->assertEquals("2008-09-12", $date->getDate());

        $this->assertEquals("2008-09-12", $date->getValue());

        $null_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, null);
        $this->assertNull($null_date->getTimestamp());
        $this->assertEquals('', $null_date->getDate());
    }

    public function testNoDiff(): void
    {
        $this->field->shouldReceive('formatDateForDisplay')->withArgs([1221221466])->andReturn("2008-09-12");
        $this->field->shouldReceive('formatDateForDisplay')->withArgs([1234567890])->andReturn("2009-02-14");

        $date_1 = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221466);
        $date_2 = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221466);
        $this->assertFalse($date_1->diff($date_2));
        $this->assertFalse($date_2->diff($date_1));
    }

    public function testDiffBetween2dates(): void
    {
        $this->field->shouldReceive('formatDateForDisplay')->withArgs([1221221466])->andReturn("2008-09-12");
        $this->field->shouldReceive('formatDateForDisplay')->withArgs([1234567890])->andReturn("2009-02-14");

        $date_1 = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1221221466);
        $date_2 = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        $this->assertEquals('changed from 2009-02-14 to 2008-09-12', $date_1->diff($date_2));
        $this->assertEquals('changed from 2008-09-12 to 2009-02-14', $date_2->diff($date_1));
    }

    public function testDiffDateSet(): void
    {
        $this->field->shouldReceive('formatDateForDisplay')->withArgs([1221221466])->andReturn("2008-09-12");
        $this->field->shouldReceive('formatDateForDisplay')->withArgs([1234567890])->andReturn("2009-02-14");

        $previous_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 0);
        $new_date      = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        $this->assertEquals('set to 2009-02-14', $new_date->diff($previous_date));
    }

    public function testDiffDateCleared(): void
    {
        $this->field->shouldReceive('formatDateForDisplay')->withArgs([1234567890])->andReturn("2009-02-14");

        $previous_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        $new_date      = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, null);
        $this->assertEquals('cleared', $new_date->diff($previous_date));
    }

    public function testDiffDateDidNotChanged(): void
    {
        $this->field->shouldReceive('formatDateForDisplay')->withArgs([1234567890])->andReturn("2009-02-14");

        $previous_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        $new_date      = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, 1234567890);
        $this->assertEquals('', $new_date->diff($previous_date));
    }

    public function testDiffNoValueSubmittedYetBothDatesAreNull(): void
    {
        $previous_date = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, null);
        $new_date      = new Tracker_Artifact_ChangesetValue_Date(111, $this->changeset, $this->field, false, null);
        $this->assertEquals('', $new_date->diff($previous_date));
    }
}
