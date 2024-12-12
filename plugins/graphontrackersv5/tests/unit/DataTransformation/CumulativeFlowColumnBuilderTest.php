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

use Tuleap\GlobalLanguageMock;

final class CumulativeFlowColumnBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private int $scale;
    private array $time_filler;
    private int $nb_steps;
    private int $start_date;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CumulativeFlowDAO
     */
    private $dao;
    private CumulativeFlowColumnBuilder $column_builder;

    protected function setUp(): void
    {
        $this->dao            = $this->createMock(CumulativeFlowDAO::class);
        $this->column_builder = new CumulativeFlowColumnBuilder($this->dao);

        $this->start_date  = 12345678;
        $this->nb_steps    = 0;
        $this->time_filler = [];
        $this->scale       = 0;
    }

    public function testItBuildEmptyColumnsWithColors(): void
    {
        $this->dao->method('getChartColors')->willReturn(
            [
                [
                    'id'             => 1,
                    'label'          => 'A tlp color',
                    'tlp_color_name' => 'inca-silver',
                ],
                [
                    'id'    => 2,
                    'label' => 'A legacy color',
                    'red'   => '255',
                    'blue'  => '255',
                    'green' => '255',
                ],
            ]
        );

        $this->dao->method('getColorOfNone')->willReturn(
            [
                'id'             => 1,
                'label'          => 'A tlp color',
                'tlp_color_name' => 'placid-blue',
            ]
        );

        $expected = [
            100 => [
                'values' => [],
                'color'  => 'placid-blue',
                'label'  => null,
                'id'     => 100,
            ],
            1 => [
                'values' => [],
                'color'  => 'inca-silver',
                'label'  => 'A tlp color',
                'id'     => 1,
            ],
            2 => [
                'values' => [],
                'color'  => '#FFFFFF',
                'label'  => 'A legacy color',
                'id'     => 2,
            ],
        ];

        self::assertEquals(
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
        $this->dao->method('getChartColors')->willReturn(
            [
                [
                    'id'             => 1,
                    'label'          => 'A tlp color',
                    'tlp_color_name' => 'inca-silver',
                ],
            ]
        );

        $this->dao->method('getColorOfNone')->willReturn(
            [
                'id'             => 1,
                'label'          => 'A tlp color',
                'red'   => '255',
                'blue'  => '255',
                'green' => '255',
            ]
        );

        $expected = [
            100 => [
                'values' => [],
                'color'  => '#FFFFFF',
                'label'  => null,
                'id'     => 100,
            ],
            1 => [
                'values' => [],
                'color'  => 'inca-silver',
                'label'  => 'A tlp color',
                'id'     => 1,
            ],
        ];

        self::assertEquals(
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
        $this->dao->method('getChartColors')->willReturn(
            [
                [
                    'id'             => 1,
                    'label'          => 'A tlp color',
                    'tlp_color_name' => 'inca-silver',
                ],
            ]
        );

        $this->dao->method('getColorOfNone')->willReturn([]);

        $expected = [
            100 => [
                'values' => [],
                'color'  => null,
                'label'  => null,
                'id'     => 100,
            ],
            1 => [
                'values' => [],
                'color'  => 'inca-silver',
                'label'  => 'A tlp color',
                'id'     => 1,
            ],
        ];

        self::assertEquals(
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
