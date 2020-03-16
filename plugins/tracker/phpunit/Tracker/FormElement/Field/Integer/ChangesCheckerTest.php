<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\Integer;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_Artifact_ChangesetValue_Integer;

require_once __DIR__ . '/../../../../bootstrap.php';

class ChangesCheckerTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->old_value = Mockery::mock(Tracker_Artifact_ChangesetValue_Integer::class);
        $this->checker   = new ChangesChecker();
    }

    public function testChecksIfChangesOccuredAtArtifactUpdate()
    {
        $this->old_value->shouldReceive('getNumeric')->andReturn(1);

        $this->assertTrue($this->checker->hasChanges($this->old_value, 2));
        $this->assertFalse($this->checker->hasChanges($this->old_value, 1));
    }

    public function testShouldBeTrueIfPreviousValueWasNullAndNewValueIsZero()
    {
        $this->old_value->shouldReceive('getNumeric')->andReturn(null);
        $new_value = 0;

        $this->assertTrue($this->checker->hasChanges($this->old_value, $new_value));
    }

    public function testShouldBeTrueIfOldValueIsZeroAndNewValueIsNull()
    {
        $this->old_value->shouldReceive('getNumeric')->andReturn(0);
        $new_value = null;

        $this->assertTrue($this->checker->hasChanges($this->old_value, $new_value));
    }

    public function testShouldBeTrueIfOldValueIsZeroAndNewValueIsEmpty()
    {
        $this->old_value->shouldReceive('getNumeric')->andReturn(0);
        $new_value = '';

        $this->assertTrue($this->checker->hasChanges($this->old_value, $new_value));
    }

    public function testShouldBeFalseWhenNoUpdateOnNullValue()
    {
        $this->old_value->shouldReceive('getNumeric')->andReturn(null);
        $new_value = null;

        $this->assertFalse($this->checker->hasChanges($this->old_value, $new_value));
    }

    public function testShouldBeFalseWhenNoUpdatingNullValueToEmpty()
    {
        $this->old_value->shouldReceive('getNumeric')->andReturn(null);
        $new_value = '';

        $this->assertFalse($this->checker->hasChanges($this->old_value, $new_value));
    }

    public function testShouldBeFalseWhenNoUpdateOnZeroValue()
    {
        $this->old_value->shouldReceive('getNumeric')->andReturn(0);
        $new_value = 0;

        $this->assertFalse($this->checker->hasChanges($this->old_value, $new_value));
    }
}
