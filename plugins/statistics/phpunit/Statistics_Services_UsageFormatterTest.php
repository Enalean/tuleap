<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Statistics;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Statistics_Formatter;
use Statistics_Services_UsageFormatter;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class Statistics_Services_UsageFormatterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Statistics_Services_UsageFormatter */
    private $usage_formatter;

    /**
     * @var array
     */
    private $first_input_datas;

    protected function setUp(): void
    {
        parent::setUp();
        $stats_formatter        = Mockery::mock(Statistics_Formatter::class);
        $this->usage_formatter  = new Statistics_Services_UsageFormatter($stats_formatter);

        $this->first_input_datas = array(
            array(
                'group_id' => 1,
                'result'   => 'res1'
            ),
            array(
                'group_id' => 87,
                'result'   => 'res2'
            ),
            array(
                'group_id' => 104,
                'result'   => 'res3'
            )
        );
    }

    public function testItBuildsData(): void
    {
        $expected = array(
            1 => array(
                "title" => 'res1'
            ),
            87 => array(
                "title" => 'res2'
            ),
            104 => array(
                "title" => 'res3'
            )
        );

        $datas = $this->usage_formatter->buildDatas($this->first_input_datas, "title");
        $this->assertEquals($datas, $expected);
    }

    public function testItOnlyAddTitlesWhithEmptyData(): void
    {
        $input_datas = array(
            array(
                'group_id' => 87,
                'result'   => 'descr2'
            )
        );

        $expected = array(
            1 => array(
                "title" => 'res1',
                "descr" => 0
            ),
            87 => array(
                "title" => 'res2',
                "descr" => 'descr2'
            ),
            104 => array(
                "title" => 'res3',
                "descr" => 0
            )
        );

        $this->usage_formatter->buildDatas($this->first_input_datas, "title");
        $datas = $this->usage_formatter->buildDatas($input_datas, "descr");

        $this->assertEquals($datas, $expected);
    }
}
