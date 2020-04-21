<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_String;
use Tracker_FormElement_Field_String;

class TrackerFormElementFieldStringChangesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact_ChangesetValue_String
     */
    private $previous_value;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_String
     */
    private $field;

    protected function setUp(): void
    {
        $this->field = Mockery::mock(Tracker_FormElement_Field_String::class)->makePartial();
        $this->previous_value = Mockery::mock(Tracker_Artifact_ChangesetValue_String::class);
        $this->previous_value->shouldReceive('getText')->andReturn('1');
    }

    public function testItReturnsTrueIfThereIsAChange()
    {
        $new_value = '1.0';

        $this->assertTrue($this->field->hasChanges(Mockery::mock(Tracker_Artifact::class), $this->previous_value, $new_value));
    }

    public function testItReturnsFalseIfThereIsNoChange()
    {
        $new_value = '1';

        $this->assertFalse($this->field->hasChanges(Mockery::mock(Tracker_Artifact::class), $this->previous_value, $new_value));
    }
}
