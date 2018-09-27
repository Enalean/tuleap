<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) Jtekt, 2014. All Rights Reserved.
 *
 * Originally written by Yoann Celton, 2014. Jtekt Europe SAS.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\GraphOnTrackersV5;

use GraphOnTrackersV5_CumulativeFlow_DataBuilder;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class CumulativeFlowChartTest extends TestCase
{
    private $data_builder;

    public function setUp()
    {
        $this->data_builder = new GraphOnTrackersV5_CumulativeFlow_DataBuilder(null, null);
    }

    public function testGetColumns()
    {
        $all_columns = [
            100 => [
                'id' => 100,
                'label' => 'None',
                'color' => null,
                'values' => [
                    1 => [
                        'date' => 1,
                        'count' => 0
                    ],
                    2 => [
                        'date' => 2,
                        'count' => 0
                    ]
                ]
            ],
            200 => [
                'id' => 200,
                'label' => 'Todo',
                'color' => 'fiesta-red',
                'values' => [
                    1 => [
                        'date' => 1,
                        'count' => 1
                    ],
                    2 => [
                        'date' => 2,
                        'count' => 6
                    ]
                ]
            ]
        ];
        $only_used_columns = [
            [
                'id' => 200,
                'label' => 'Todo',
                'color' => 'fiesta-red',
                'values' => [
                    [
                        'date' => 1,
                        'count' => 1
                    ], [
                        'date' => 2,
                        'count' => 6
                    ]
                ]
            ]
        ];

        $this->assertEquals($this->data_builder->getColumns($all_columns), $only_used_columns);
    }
}
