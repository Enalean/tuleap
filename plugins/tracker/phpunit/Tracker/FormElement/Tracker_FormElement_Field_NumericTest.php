<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once dirname(__FILE__) . '/../../bootstrap.php';

class Tracker_FormElement_Field_NumericTest extends \PHPUnit\Framework\TestCase // @codingStandardsIgnoreLine
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Tracker_FormElement_Field_Integer
     */
    private $int_field;
    /**
     * @var Tracker_Artifact_ChangesetValue_Integer
     */
    private $old_value;
    /**
     * @var Tracker_Artifact
     */
    private $artifact;

    protected function setUp()
    {
        parent::setUp();

        $this->artifact = Mockery::mock(Tracker_Artifact::class);
        $this->old_value = Mockery::mock(Tracker_Artifact_ChangesetValue_Integer::class);

        $this->int_field = new Tracker_FormElement_Field_Integer(1, 1, 1, 'int field', 'int field', 'int field', 1, 1, 1, 0, 100);
    }

    public function testShouldBeTrueIfPreviousValueWasNullAndNewValueIsZero()
    {
        $this->old_value->shouldReceive('getNumeric')->andReturn(null);
        $new_value = 0;

        $this->assertTrue($this->int_field->hasChanges($this->artifact, $this->old_value, $new_value));
    }

    public function testShouldBeTrueIfOldValueIsZeroAndNewValueIsNull()
    {
        $this->old_value->shouldReceive('getNumeric')->andReturn(0);
        $new_value = null;

        $this->assertTrue($this->int_field->hasChanges($this->artifact, $this->old_value, $new_value));
    }

    public function testShouldBeTrueIfValueHasCahnged()
    {
        $this->old_value->shouldReceive('getNumeric')->andReturn(10);
        $new_value = 20;

        $this->assertTrue($this->int_field->hasChanges($this->artifact, $this->old_value, $new_value));
    }

    public function testShouldBeFalseWhenNoUpdateOnNullValue()
    {
        $this->old_value->shouldReceive('getNumeric')->andReturn(null);
        $new_value = null;

        $this->assertFalse($this->int_field->hasChanges($this->artifact, $this->old_value, $new_value));
    }

    public function testShouldBeFalseWhenNoUpdateOnZeroValue()
    {
        $this->old_value->shouldReceive('getNumeric')->andReturn(0);
        $new_value = 0;

        $this->assertFalse($this->int_field->hasChanges($this->artifact, $this->old_value, $new_value));
    }
}
