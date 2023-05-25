<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
use Tracker_FormElement_Field_Selectbox;
use Tracker_Report_Criteria;

require_once __DIR__ . '/bootstrap.php';

final class CumulativeFlowChartTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private GraphOnTrackersV5_CumulativeFlow_DataBuilder $data_builder;
    private \PHPUnit\Framework\MockObject\MockObject&\Tracker_Report $report;

    public function setUp(): void
    {
        $this->report = $this->createMock(\Tracker_Report::class);

        $renderer = $this->createMock(\GraphOnTrackersV5_Renderer::class);
        $renderer->method('getReport')->willReturn($this->report);

        $chart = $this->createMock(\GraphOnTrackersV5_Chart_CumulativeFlow::class);
        $chart->method('getRenderer')->willReturn($renderer);
        $chart->method('getFieldId')->willReturn(201);

        $this->data_builder = new GraphOnTrackersV5_CumulativeFlow_DataBuilder($chart, null);
    }

    public function testGetColumns(): void
    {
        $this->report->expects(self::once())->method('getCriteria')->willReturn([]);

        $all_columns       = [
            100 => [
                'id' => 100,
                'label' => 'None',
                'color' => null,
                'values' => [
                    1 => [
                        'date' => 1,
                        'count' => 0,
                    ],
                    2 => [
                        'date' => 2,
                        'count' => 0,
                    ],
                ],
            ],
            200 => [
                'id' => 200,
                'label' => 'Todo',
                'color' => 'fiesta-red',
                'values' => [
                    1 => [
                        'date' => 1,
                        'count' => 1,
                    ],
                    2 => [
                        'date' => 2,
                        'count' => 6,
                    ],
                ],
            ],
        ];
        $only_used_columns = [
            [
                'id' => 200,
                'label' => 'Todo',
                'color' => 'fiesta-red',
                'values' => [
                    [
                        'date' => 1,
                        'count' => 1,
                    ], [
                        'date' => 2,
                        'count' => 6,
                    ],
                ],
            ],
        ];

        self::assertEquals($this->data_builder->getColumns($all_columns), $only_used_columns);
    }

    public function testColumnsAreFilterWithReportCriteria(): void
    {
        $criterion       = $this->createMock(Tracker_Report_Criteria::class);
        $criterion_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);

        $criterion_field->method('getId')->willReturn('201');
        $criterion_field->method('getCriteriaValue')->with($criterion)->willReturn([
            101,
            102,
        ]);

        $criterion->method('getField')->willReturn($criterion_field);

        $this->report->expects(self::once())->method('getCriteria')->willReturn([$criterion]);

        $all_columns = [
            100 => [
                'id' => 100,
                'label' => 'None',
                'color' => null,
                'values' => [
                    1 => [
                        'date' => 1,
                        'count' => 0,
                    ],
                    2 => [
                        'date' => 2,
                        'count' => 0,
                    ],
                ],
            ],
            101 => [
                'id' => 101,
                'label' => 'Todo',
                'color' => 'fiesta-red',
                'values' => [
                    1 => [
                        'date' => 1,
                        'count' => 1,
                    ],
                    2 => [
                        'date' => 2,
                        'count' => 6,
                    ],
                ],
            ],
            102 => [
                'id' => 102,
                'label' => 'OnGoing',
                'color' => 'fiesta-red',
                'values' => [
                    1 => [
                        'date' => 1,
                        'count' => 1,
                    ],
                    2 => [
                        'date' => 2,
                        'count' => 6,
                    ],
                ],
            ],
            103 => [
                'id' => 103,
                'label' => 'Done',
                'color' => 'fiesta-red',
                'values' => [
                    1 => [
                        'date' => 1,
                        'count' => 1,
                    ],
                    2 => [
                        'date' => 2,
                        'count' => 6,
                    ],
                ],
            ],
        ];

        $returned_columns = $this->data_builder->getColumns($all_columns);

        self::assertCount(2, $returned_columns);
    }

    public function testColumnsAreNotFilterWithReportCriteriaDefinedOnAny(): void
    {
        $criterion       = $this->createMock(Tracker_Report_Criteria::class);
        $criterion_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);

        $criterion_field->method('getId')->willReturn('201');
        $criterion_field->method('getCriteriaValue')->with($criterion)->willReturn('');

        $criterion->method('getField')->willReturn($criterion_field);

        $this->report->expects(self::once())->method('getCriteria')->willReturn([$criterion]);

        $all_columns = [
            101 => [
                'id' => 101,
                'label' => 'Todo',
                'color' => 'fiesta-red',
                'values' => [
                    1 => [
                        'date' => 1,
                        'count' => 1,
                    ],
                    2 => [
                        'date' => 2,
                        'count' => 6,
                    ],
                ],
            ],
            102 => [
                'id' => 102,
                'label' => 'OnGoing',
                'color' => 'fiesta-red',
                'values' => [
                    1 => [
                        'date' => 1,
                        'count' => 1,
                    ],
                    2 => [
                        'date' => 2,
                        'count' => 6,
                    ],
                ],
            ],
            103 => [
                'id' => 103,
                'label' => 'Done',
                'color' => 'fiesta-red',
                'values' => [
                    1 => [
                        'date' => 1,
                        'count' => 1,
                    ],
                    2 => [
                        'date' => 2,
                        'count' => 6,
                    ],
                ],
            ],
        ];

        $returned_columns = $this->data_builder->getColumns($all_columns);

        self::assertCount(3, $returned_columns);
    }
}
