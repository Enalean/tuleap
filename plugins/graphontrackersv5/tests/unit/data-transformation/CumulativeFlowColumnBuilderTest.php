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

declare(strict_types=1);

namespace Tuleap\GraphOnTrackersV5\DataTransformation;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;

final class CumulativeFlowColumnBuilderTest extends TestCase
{
    use GlobalLanguageMock;
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var int
     */
    private $scale;

    /**
     * @var array
     */
    private $time_filler;

    /**
     * @var int
     */
    private $nb_steps;

    /**
     * @var int
     */
    private $start_date;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CumulativeFlowDAO
     */
    private $dao;
    /**
     * @var \Mockery\Mock | GraphOnTrackersV5_CumulativeFlow_DataBuilder
     */
    private $column_builder;

    protected function setUp(): void
    {
        $this->dao            = \Mockery::mock(CumulativeFlowDAO::class);
        $this->column_builder = new CumulativeFlowColumnBuilder($this->dao);

        $this->start_date  = 12345678;
        $this->nb_steps    = 0;
        $this->time_filler = [];
        $this->scale       = 0;
    }

    public function testItBuildEmptyColumnsWithColors(): void
    {
        $this->dao->shouldReceive('getChartColors')->andReturn(
            [
                [
                    'id'             => 1,
                    'label'          => 'A tlp color',
                    'tlp_color_name' => 'inca-silver'
                ],
                [
                    'id'    => 2,
                    'label' => 'A legacy color',
                    'red'   => '255',
                    'blue'  => '255',
                    'green' => '255'
                ]
            ]
        );

        $this->dao->shouldReceive('getColorOfNone')->andReturn(
            [
                'id'             => 1,
                'label'          => 'A tlp color',
                'tlp_color_name' => 'placid-blue'
            ]
        );

        $expected[100] = [
            'values' => [],
            'color'  => 'placid-blue',
            'label'  => null,
            'id'     => 100
        ];
        $expected[1]   = [
            'values' => [],
            'color'  => 'inca-silver',
            'label'  => 'A tlp color',
            'id'     => 1
        ];
        $expected[2]   = [
            'values' => [],
            'color'  => '#FFFFFF',
            'label'  => 'A legacy color',
            'id'     => 2
        ];

        $this->assertEquals(
            $expected,
            $this->column_builder->initEmptyColumns(
                123,
                $this->start_date,
                $this->nb_steps,
                $this->time_filler,
                $this->scale
            )
        );
    }

    public function testItBuildEmptyColumnsWithLegacyColors(): void
    {
        $this->dao->shouldReceive('getChartColors')->andReturn(
            [
                [
                    'id'             => 1,
                    'label'          => 'A tlp color',
                    'tlp_color_name' => 'inca-silver'
                ]
            ]
        );

        $this->dao->shouldReceive('getColorOfNone')->andReturn(
            [
                'id'             => 1,
                'label'          => 'A tlp color',
                'red'   => '255',
                'blue'  => '255',
                'green' => '255'
            ]
        );

        $expected[100] = [
            'values' => [],
            'color'  => '#FFFFFF',
            'label'  => null,
            'id'     => 100
        ];
        $expected[1]   = [
            'values' => [],
            'color'  => 'inca-silver',
            'label'  => 'A tlp color',
            'id'     => 1
        ];

        $this->assertEquals(
            $expected,
            $this->column_builder->initEmptyColumns(
                123,
                $this->start_date,
                $this->nb_steps,
                $this->time_filler,
                $this->scale
            )
        );
    }

    public function testItBuildEmptyColumnsWithoutNoneColor(): void
    {
        $this->dao->shouldReceive('getChartColors')->andReturn(
            [
                [
                    'id'             => 1,
                    'label'          => 'A tlp color',
                    'tlp_color_name' => 'inca-silver'
                ]
            ]
        );

        $this->dao->shouldReceive('getColorOfNone')->andReturn([]);

        $expected[100] = [
            'values' => [],
            'color'  => null,
            'label'  => null,
            'id'     => 100
        ];
        $expected[1]   = [
            'values' => [],
            'color'  => 'inca-silver',
            'label'  => 'A tlp color',
            'id'     => 1
        ];

        $this->assertEquals(
            $expected,
            $this->column_builder->initEmptyColumns(
                123,
                $this->start_date,
                $this->nb_steps,
                $this->time_filler,
                $this->scale
            )
        );
    }
}
