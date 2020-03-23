<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2010. All rights reserved
 * Copyright (c) Enalean, 2017-present. All rights reserved
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\common\date\helper;

use DateHelper;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;

final class DateHelperTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    public function testDistanceOfTimeInWords(): void
    {
        $expected = array(
            0        => array( //'less than a minute', 'less than 5 seconds'),     // 0 second
                               array('include_utils', 'less_1_minute'),
                               array('include_utils', 'less_than_one_second', '*'),
            ),
            2        => array( //'less than a minute', 'less than 5 seconds'),     // 2 seconds
                               array('include_utils', 'less_1_minute'),
                               array('include_utils', 'less_than_X_seconds', '*'),
            ),
            7        => array( //'less than a minute', 'less than 10 seconds'),    // 7 seconds
                               array('include_utils', 'less_1_minute'),
                               array('include_utils', 'less_than_X_seconds', '*'),
            ),
            12       => array( //'less than a minute', 'less than 20 seconds'),    // 12 seconds
                               array('include_utils', 'less_1_minute'),
                               array('include_utils', 'less_than_X_seconds', '*'),
            ),
            21       => array( //'less than a minute', 'half a minute'),           // 21 seconds
                               array('include_utils', 'less_1_minute'),
                               array('include_utils', 'half_a_minute'),
            ),
            30       => array( //'1 minute',           'half a minute'),           // 30 seconds
                               array('include_utils', '1_minute'),
                               array('include_utils', 'half_a_minute'),
            ),
            50       => array( //'1 minute',           'less than a minute'),      // 50 seconds
                               array('include_utils', '1_minute'),
                               array('include_utils', 'less_1_minute'),
            ),
            60       => array( //'1 minute',           '1 minute'),                // 60 seconds
                               array('include_utils', '1_minute'),
                               array('include_utils', '1_minute'),
            ),
            90       => array( //'2 minutes',          '2 minutes'),               // 90 seconds
                               array('include_utils', 'X_minutes', '*'),
                               array('include_utils', 'X_minutes', '*'),
            ),
            130      => array( //'2 minutes',          '2 minutes'),               // 130 seconds
                               array('include_utils', 'X_minutes', '*'),
                               array('include_utils', 'X_minutes', '*'),
            ),
            3000     => array( //'about 1 hour',       'about 1 hour'),            // 50*60 seconds
                               array('include_utils', 'about_1_hour'),
                               array('include_utils', 'about_1_hour'),
            ),
            6000     => array( //'about 2 hours',      'about 2 hours'),           // 100*60 seconds
                               array('include_utils', 'about_X_hours', '*'),
                               array('include_utils', 'about_X_hours', '*'),
            ),
            87000    => array( //'1 day',              '1 day'),                   // 1450*60 seconds
                               array('include_utils', 'about_1_day'),
                               array('include_utils', 'about_1_day'),
            ),
            172860   => array( //'2 days',             '2 days'),                  // 2881*60 seconds
                               array('include_utils', 'X_days', '*'),
                               array('include_utils', 'X_days', '*'),
            ),
            2592060  => array( //'about 1 month',      'about 1 month'),           // 43201*60 seconds
                               array('include_utils', 'about_1_month'),
                               array('include_utils', 'about_1_month'),
            ),
            5184060  => array( //'2 months',           '2 months'),                // 86401*60 seconds
                               array('include_utils', 'X_months', '*'),
                               array('include_utils', 'X_months', '*'),
            ),
            31557660 => array( //'about 1 year',       'about 1 year'),            // 525961*60 seconds
                               array('include_utils', 'about_1_year'),
                               array('include_utils', 'about_1_year'),
            ),
            63115200 => array( //'over 2 years',       'over 2 years'),            // 1051920*60 seconds
                               array('include_utils', 'over_X_years', '*'),
                               array('include_utils', 'over_X_years', '*'),
            ),
        );
        foreach ($expected as $distance => $e) {
            $GLOBALS['Language']->shouldReceive('getText')->times(2);
            DateHelper::distanceOfTimeInWords($_SERVER['REQUEST_TIME'] - $distance, $_SERVER['REQUEST_TIME']);
            DateHelper::distanceOfTimeInWords($_SERVER['REQUEST_TIME'] - $distance, $_SERVER['REQUEST_TIME'], true);
        }
    }

    public function testFormatDateFormatsTheDateAccordingToLanguage(): void
    {
        $dayOnly = true;
        $this->assertRegExp('/2011-\d+-\d+/', $this->formatDate($dayOnly, 'Y-m-d'));
        $this->assertRegExp('/2011\/\d+\/\d+/', $this->formatDate($dayOnly, "Y/d/m"));
    }

    public function testFormatDateCanReturnTheTimeAsWell(): void
    {
        $dayOnly = false;
        $this->assertRegExp('/2011-\d+-\d+ \d+:\d+/', $this->formatDate($dayOnly, "Y-m-d h:i"));
    }

    private function formatDate($dayOnly, $format)
    {
        $lang = \Mockery::mock(\BaseLanguage::class);
        $lang->shouldReceive('getText')->andReturn($format);
        $firstOfDecember2011_12_01 = 1322752769;
        return DateHelper::formatForLanguage($lang, $firstOfDecember2011_12_01, $dayOnly);
    }

    public function testDateInPast(): void
    {
        $GLOBALS['Language']
            ->shouldReceive('getText')
            ->with(
                'include_utils',
                'X_minutes',
                8
            )
            ->once()
            ->andReturns('8 minutes');

        $this->assertEquals(
            '8 minutes ago',
            DateHelper::timeAgoInWords($_SERVER['REQUEST_TIME'] - 500)
        );
    }

    public function testDateInFuture()
    {
        $GLOBALS['Language']
            ->shouldReceive('getText')
            ->with(
                'include_utils',
                'X_minutes',
                8
            )
            ->once()
            ->andReturns('8 minutes');

        $this->assertEquals(
            'in 8 minutes',
            DateHelper::timeAgoInWords($_SERVER['REQUEST_TIME'] + 500)
        );
    }
}
