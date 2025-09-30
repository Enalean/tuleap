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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_FormElement_Container_Column_Group;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Container\Column\ColumnContainer;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Container_Column_GroupTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    public function testFetchArtifact(): void
    {
        $artifact         = ArtifactTestBuilder::anArtifact(65412)->build();
        $submitted_values = [];

        $column_01 = $this->createMock(ColumnContainer::class);
        $column_01->method('fetchArtifactInGroup')->with($artifact, $submitted_values)->willReturn('C1');

        $column_02 = $this->createMock(ColumnContainer::class);
        $column_02->method('fetchArtifactInGroup')->with($artifact, $submitted_values)->willReturn('C2');

        $column_03 = $this->createMock(ColumnContainer::class);
        $column_03->method('fetchArtifactInGroup')->with($artifact, $submitted_values)->willReturn('C3');

        $column_04 = $this->createMock(ColumnContainer::class);
        $column_04->method('fetchArtifactInGroup')->with($artifact, $submitted_values)->willReturn('C4');

        $empty = [];
        $one   = [$column_01];
        $many  = [$column_01, $column_02, $column_03, $column_04];

        $column_group = new Tracker_FormElement_Container_Column_Group();

        self::assertEquals(
            '',
            $column_group->fetchArtifact($empty, $artifact, $submitted_values)
        );

        self::assertEquals(
            '<table width="100%"><tbody><tr valign="top">' .
            '<td>C1</td>' .
            '</tr></tbody></table>',
            $column_group->fetchArtifact($one, $artifact, $submitted_values)
        );
        self::assertEquals(
            '<table width="100%"><tbody><tr valign="top">' .
            '<td>C1</td>' .
            '<td>C2</td>' .
            '<td>C3</td>' .
            '<td>C4</td>' .
            '</tr></tbody></table>',
            $column_group->fetchArtifact($many, $artifact, $submitted_values)
        );
    }

    public function testFetchArtifactWithEmptyColumns(): void
    {
        $artifact         = ArtifactTestBuilder::anArtifact(65412)->build();
        $submitted_values = [];

        $column_01 = $this->createMock(ColumnContainer::class);
        $column_01->method('fetchArtifactInGroup')->with($artifact, $submitted_values)->willReturn('');

        $column_02 = $this->createMock(ColumnContainer::class);
        $column_02->method('fetchArtifactInGroup')->with($artifact, $submitted_values)->willReturn('C2');

        $column_03 = $this->createMock(ColumnContainer::class);
        $column_03->method('fetchArtifactInGroup')->with($artifact, $submitted_values)->willReturn('');

        $column_04 = $this->createMock(ColumnContainer::class);
        $column_04->method('fetchArtifactInGroup')->with($artifact, $submitted_values)->willReturn('C4');

        $one_c1 = [$column_01];
        $one_c2 = [$column_02];
        $many   = [$column_01, $column_02, $column_03, $column_04];

        $column_group = new Tracker_FormElement_Container_Column_Group();

        self::assertEquals('', $column_group->fetchArtifact($one_c1, $artifact, $submitted_values));

        self::assertEquals(
            '<table width="100%"><tbody><tr valign="top">' .
            '<td>C2</td>' .
            '</tr></tbody></table>',
            $column_group->fetchArtifact($one_c2, $artifact, $submitted_values)
        );
        self::assertEquals(
            '<table width="100%"><tbody><tr valign="top">' .
            '<td>C2</td>' .
            '<td>C4</td>' .
            '</tr></tbody></table>',
            $column_group->fetchArtifact($many, $artifact, $submitted_values)
        );
    }
}
