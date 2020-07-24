<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;
use Statistics_Formatter;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class Statistics_FormatterTest extends TestCase
{
    public function testItExportsCsv(): void
    {
        $data_line            = ['Data1,', 'Data 2', 'Data 3', 'Data 4', 'Data5'];
        $statistics_formatter = new Statistics_Formatter('', '', ',', ',');
        $statistics_formatter->addHeader('Title');
        $statistics_formatter->addLine($data_line);
        $statistics_formatter->addEmptyLine();
        $statistics_formatter->addLine($data_line);
        $this->assertEquals($statistics_formatter->getCsvContent(), '
Title
"Data1,","Data 2","Data 3","Data 4",Data5

"Data1,","Data 2","Data 3","Data 4",Data5
');
    }

    public function testItClearsContent(): void
    {
        $data_line            = ['Data', 'Data', 'Data'];
        $statistics_formatter = new Statistics_Formatter('', '', ',');
        $statistics_formatter->addHeader('Title');
        $statistics_formatter->addLine($data_line);
        $statistics_formatter->addEmptyLine();
        $statistics_formatter->clearContent();
        $this->assertEquals($statistics_formatter->getCsvContent(), '');
    }

    public function testItExportsMultipleTimes(): void
    {
        $data_line            = ['Data', 'Data', 'Data'];
        $statistics_formatter = new Statistics_Formatter('', '', ',');
        $statistics_formatter->addLine($data_line);
        $this->assertEquals($statistics_formatter->getCsvContent(), PHP_EOL . 'Data,Data,Data' . PHP_EOL);
        $this->assertEquals($statistics_formatter->getCsvContent(), PHP_EOL . 'Data,Data,Data' . PHP_EOL);
        $statistics_formatter->addLine($data_line);
        $this->assertEquals(
            $statistics_formatter->getCsvContent(),
            PHP_EOL . 'Data,Data,Data' . PHP_EOL . 'Data,Data,Data' . PHP_EOL
        );
    }
}
