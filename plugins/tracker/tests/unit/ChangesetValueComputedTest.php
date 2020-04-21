<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Mockery;
use PHPUnit\Framework\TestCase;

class ChangesetValueComputedTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_Artifact_Changeset
     */
    private $changeset;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_FormElement_Field_Computed
     */
    private $field;

    protected function setUp(): void
    {
        parent::setUp();

        $this->field = Mockery::mock(\Tracker_FormElement_Field_Computed::class);

        $this->changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
    }


    public function testNoDiff(): void
    {
        $float_1 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 456.789, true);
        $float_2 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 456.789, true);
        $this->assertFalse($float_1->diff($float_2));
        $this->assertFalse($float_2->diff($float_1));

        $float_1 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 0, true);
        $float_2 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 0, true);
        $this->assertFalse($float_1->diff($float_2));
        $this->assertFalse($float_2->diff($float_1));

        $float_1 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, null, true);
        $float_2 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, null, true);
        $this->assertFalse($float_1->diff($float_2));
        $this->assertFalse($float_2->diff($float_1));
    }

    public function testDiff(): void
    {
        $float_1 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 987.321, true);
        $float_2 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 987, true);

        $this->assertEquals($float_1->diff($float_2), 'changed from 987 to 987.321');
        $this->assertEquals($float_2->diff($float_1), 'changed from 987.321 to 987');

        $float_5 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 987.4321, true);
        $float_6 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 987.4329, true);
        $this->assertEquals($float_5->diff($float_6), 'changed from 987.4329 to 987.4321');
        $this->assertEquals($float_6->diff($float_5), 'changed from 987.4321 to 987.4329');

        $float_7 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 0, true);
        $float_8 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 1233, true);
        $this->assertEquals($float_7->diff($float_8), 'changed from 1233 to 0');
        $this->assertEquals($float_8->diff($float_7), 'changed from 0 to 1233');

        $float_9  = new ChangesetValueComputed(111, $this->changeset, $this->field, false, null, false);
        $float_10 = new ChangesetValueComputed(111, $this->changeset, $this->field, false, 1233, true);
        $this->assertEquals($float_9->diff($float_10), 'changed from 1233 to autocomputed');
        $this->assertEquals($float_10->diff($float_9), 'changed from autocomputed to 1233');
    }
}
