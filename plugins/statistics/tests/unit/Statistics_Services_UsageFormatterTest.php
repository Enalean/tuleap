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

use Statistics_Formatter;
use Statistics_Services_UsageFormatter;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Statistics_Services_UsageFormatterTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    /** @var Statistics_Services_UsageFormatter */
    private $usage_formatter;

    /**
     * @var array
     */
    private $first_input_datas;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $stats_formatter       = $this->createMock(Statistics_Formatter::class);
        $this->usage_formatter = new Statistics_Services_UsageFormatter($stats_formatter);

        $this->first_input_datas = [
            [
                'group_id' => 1,
                'result'   => 'res1',
            ],
            [
                'group_id' => 87,
                'result'   => 'res2',
            ],
            [
                'group_id' => 104,
                'result'   => 'res3',
            ],
        ];
    }

    public function testItBuildsData(): void
    {
        $expected = [
            1 => [
                'title' => 'res1',
            ],
            87 => [
                'title' => 'res2',
            ],
            104 => [
                'title' => 'res3',
            ],
        ];

        $datas = $this->usage_formatter->buildDatas($this->first_input_datas, 'title');
        self::assertEquals($datas, $expected);
    }

    public function testItOnlyAddTitlesWithEmptyData(): void
    {
        $input_datas = [
            [
                'group_id' => 87,
                'result'   => 'descr2',
            ],
        ];

        $expected = [
            1 => [
                'title' => 'res1',
                'descr' => 0,
            ],
            87 => [
                'title' => 'res2',
                'descr' => 'descr2',
            ],
            104 => [
                'title' => 'res3',
                'descr' => 0,
            ],
        ];

        $this->usage_formatter->buildDatas($this->first_input_datas, 'title');
        $datas = $this->usage_formatter->buildDatas($input_datas, 'descr');

        self::assertEquals($datas, $expected);
    }
}
