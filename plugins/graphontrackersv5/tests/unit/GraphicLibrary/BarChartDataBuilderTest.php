<?php
/**
 * Copyright (c) Enalean 2021-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

namespace Tuleap\GraphOnTrackersV5\GraphicLibrary;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BarChartDataBuilderTest extends TestCase
{
    /**
     * In order to understand data bar chart render is:
     *                                               15
     *                                    14      +_____+
     *                             13   +____+    |     |
     *                           +____+ |    |    |     |
     *                           |    | |    |    |     |
     *                 11        |    | |    |    |     |
     *   10          +-----+     |    | |    |    |     |
     * +----+        |     |     |    | |    |    |     |
     * |    |        |     |     |    | |    |    |     |
     * |    |        |     |     |    | |    |    |     |
     * |    |        |     |     |    | |    |    |     |
     * |    |        |     |     |    | |    |    |     |
     * |    |        |     |     |    | |    |    |     |
     * |    |        |     |     |    | |    |    |     |
     * |    |        |     |     |    | |    |    |     |
     * |    |        |     |     |    | |    |    |     |
     * |    |        |     |     |    | |    |    |     |
     * |None|        | None|     | Abc| |Def |    |Def  |
     * +----+        +-----+     +----+ +----+    +-----+
     * 27-28 April   3-4 May       1-2 June       18-19 July
     *
     */
    public function testItKeepsOrderedLabelWhenConstructingData(): void
    {
        $engine         = new GraphOnTrackersV5_Engine_Bar();
        $engine->legend = [
            '' => 'None',
            1 => 'Abc',
            2 => 'Def',
        ];
        $engine->xaxis  = [
            100 => '27-28 April',
            101 => '3-4 May',
            102 => '1-2 June',
            103 => '18-19 July',
        ];
        $engine->labels = [
            100 => '27-28 April',
            101 => '3-4 May',
            102 => '1-2 June',
            103 => '18-19 July',
        ];
        $engine->data   = [
            '' => [100 => 10, 101 => 11],
            1  => [102 => 13],
            2  => [102 => 14, 103 => 15],
        ];

        $ordered_data = [
            [
                'label'  => '27-28 April',
                'values' => [
                    [ 'label' => 'None', 'value' => 10 ],
                    [ 'label' => 'Abc', 'value' => '' ],
                    [ 'label' => 'Def', 'value' => '' ],
                ],
            ],
            [
                'label'  => '3-4 May',
                'values' => [
                    [ 'label' => 'None', 'value' => 11 ],
                    [ 'label' => 'Abc', 'value' => '' ],
                    [ 'label' => 'Def', 'value' => '' ],
                ],
            ],
            [
                'label'  => '1-2 June',
                'values' => [
                    [ 'label' => 'None', 'value' => '' ],
                    [ 'label' => 'Abc', 'value' => 13 ],
                    [ 'label' => 'Def', 'value' => 14 ],
                ],
            ],
            [
                'label'  => '18-19 July',
                'values' => [
                    [ 'label' => 'None', 'value' => '' ],
                    [ 'label' => 'Abc', 'value' => '' ],
                    [ 'label' => 'Def', 'value' => 15 ],
                ],
            ],
        ];

        $builder = new BarChartDataBuilder();

        self::assertEquals($ordered_data, $builder->buildGroupedBarChartData($engine));
    }

    public function testBuildsChartDataWithNoneValues(): void
    {
        $engine        = new GraphOnTrackersV5_Engine_Bar();
        $engine->xaxis = [
            '' => 'None',
        ];
        $engine->data  = [
            377 => ['' => 1],
        ];

        $builder = new BarChartDataBuilder();
        self::assertEquals([['label' => 'None', 'values' => []]], $builder->buildGroupedBarChartData($engine));
    }
}
