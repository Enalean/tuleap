<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_FormElement_Container_Column;
use Tracker_FormElement_Container_Column_Group;

class Tracker_FormElement_Container_Column_GroupTest extends TestCase //phpcs:ignore
{
    use MockeryPHPUnitIntegration;

    public function testFetchArtifact()
    {
        $artifact         = Mockery::mock(Tracker_Artifact::class);
        $submitted_values = [];

        $column_01 = Mockery::mock(Tracker_FormElement_Container_Column::class);
        $column_01->shouldReceive('fetchArtifactInGroup')->with($artifact, $submitted_values)->andReturns('C1');

        $column_02 = Mockery::mock(Tracker_FormElement_Container_Column::class);
        $column_02->shouldReceive('fetchArtifactInGroup')->with($artifact, $submitted_values)->andReturns('C2');

        $column_03 = Mockery::mock(Tracker_FormElement_Container_Column::class);
        $column_03->shouldReceive('fetchArtifactInGroup')->with($artifact, $submitted_values)->andReturns('C3');

        $column_04 = Mockery::mock(Tracker_FormElement_Container_Column::class);
        $column_04->shouldReceive('fetchArtifactInGroup')->with($artifact, $submitted_values)->andReturns('C4');

        $empty = array();
        $one   = array($column_01);
        $many  = array($column_01, $column_02, $column_03, $column_04);

        $column_group = new Tracker_FormElement_Container_Column_Group();

        $this->assertEquals(
            '',
            $column_group->fetchArtifact($empty, $artifact, $submitted_values)
        );

        $this->assertEquals(
            '<table width="100%"><tbody><tr valign="top">' .
            '<td>C1</td>' .
            '</tr></tbody></table>',
            $column_group->fetchArtifact($one, $artifact, $submitted_values)
        );
        $this->assertEquals(
            '<table width="100%"><tbody><tr valign="top">' .
            '<td>C1</td>' .
            '<td>C2</td>' .
            '<td>C3</td>' .
            '<td>C4</td>' .
            '</tr></tbody></table>',
            $column_group->fetchArtifact($many, $artifact, $submitted_values)
        );
    }

    public function testFetchArtifactWithEmptyColumns()
    {
        $artifact         = Mockery::mock(Tracker_Artifact::class);
        $submitted_values = [];

        $column_01 = Mockery::mock(Tracker_FormElement_Container_Column::class);
        $column_01->shouldReceive('fetchArtifactInGroup')->with($artifact, $submitted_values)->andReturns('');

        $column_02 = Mockery::mock(Tracker_FormElement_Container_Column::class);
        $column_02->shouldReceive('fetchArtifactInGroup')->with($artifact, $submitted_values)->andReturns('C2');

        $column_03 = Mockery::mock(Tracker_FormElement_Container_Column::class);
        $column_03->shouldReceive('fetchArtifactInGroup')->with($artifact, $submitted_values)->andReturns('');

        $column_04 = Mockery::mock(Tracker_FormElement_Container_Column::class);
        $column_04->shouldReceive('fetchArtifactInGroup')->with($artifact, $submitted_values)->andReturns('C4');

        $one_c1   = array($column_01);
        $one_c2   = array($column_02);
        $many     = array($column_01, $column_02, $column_03, $column_04);

        $column_group = new Tracker_FormElement_Container_Column_Group();

        $this->assertEquals('', $column_group->fetchArtifact($one_c1, $artifact, $submitted_values));

        $this->assertEquals(
            '<table width="100%"><tbody><tr valign="top">' .
            '<td>C2</td>' .
            '</tr></tbody></table>',
            $column_group->fetchArtifact($one_c2, $artifact, $submitted_values)
        );
        $this->assertEquals(
            '<table width="100%"><tbody><tr valign="top">' .
            '<td>C2</td>' .
            '<td>C4</td>' .
            '</tr></tbody></table>',
            $column_group->fetchArtifact($many, $artifact, $submitted_values)
        );
    }
}
