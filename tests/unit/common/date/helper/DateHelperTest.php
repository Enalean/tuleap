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
use Tuleap\date\DefaultRelativeDatesDisplayPreferenceRetriever;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\UserTestBuilder;

final class DateHelperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    public function testDistanceOfTimeInWords(): void
    {
        $expected = [
            0 => [ //'less than a minute', 'less than 5 seconds'),     // 0 second
                ['include_utils', 'less_1_minute'],
                ['include_utils', 'less_than_one_second', '*'],
            ],
            2 => [ //'less than a minute', 'less than 5 seconds'),     // 2 seconds
                ['include_utils', 'less_1_minute'],
                ['include_utils', 'less_than_X_seconds', '*'],
            ],
            7 => [ //'less than a minute', 'less than 10 seconds'),    // 7 seconds
                ['include_utils', 'less_1_minute'],
                ['include_utils', 'less_than_X_seconds', '*'],
            ],
            12 => [ //'less than a minute', 'less than 20 seconds'),    // 12 seconds
                ['include_utils', 'less_1_minute'],
                ['include_utils', 'less_than_X_seconds', '*'],
            ],
            21 => [ //'less than a minute', 'half a minute'),           // 21 seconds
                ['include_utils', 'less_1_minute'],
                ['include_utils', 'half_a_minute'],
            ],
            30 => [ //'1 minute',           'half a minute'),           // 30 seconds
                ['include_utils', '1_minute'],
                ['include_utils', 'half_a_minute'],
            ],
            50 => [ //'1 minute',           'less than a minute'),      // 50 seconds
                ['include_utils', '1_minute'],
                ['include_utils', 'less_1_minute'],
            ],
            60 => [ //'1 minute',           '1 minute'),                // 60 seconds
                ['include_utils', '1_minute'],
                ['include_utils', '1_minute'],
            ],
            90 => [ //'2 minutes',          '2 minutes'),               // 90 seconds
                ['include_utils', 'X_minutes', '*'],
                ['include_utils', 'X_minutes', '*'],
            ],
            130 => [ //'2 minutes',          '2 minutes'),               // 130 seconds
                ['include_utils', 'X_minutes', '*'],
                ['include_utils', 'X_minutes', '*'],
            ],
            3000 => [ //'about 1 hour',       'about 1 hour'),            // 50*60 seconds
                ['include_utils', 'about_1_hour'],
                ['include_utils', 'about_1_hour'],
            ],
            6000 => [ //'about 2 hours',      'about 2 hours'),           // 100*60 seconds
                ['include_utils', 'about_X_hours', '*'],
                ['include_utils', 'about_X_hours', '*'],
            ],
            87000 => [ //'1 day',              '1 day'),                   // 1450*60 seconds
                ['include_utils', 'about_1_day'],
                ['include_utils', 'about_1_day'],
            ],
            172860 => [ //'2 days',             '2 days'),                  // 2881*60 seconds
                ['include_utils', 'X_days', '*'],
                ['include_utils', 'X_days', '*'],
            ],
            2592060 => [ //'about 1 month',      'about 1 month'),           // 43201*60 seconds
                ['include_utils', 'about_1_month'],
                ['include_utils', 'about_1_month'],
            ],
            5184060 => [ //'2 months',           '2 months'),                // 86401*60 seconds
                ['include_utils', 'X_months', '*'],
                ['include_utils', 'X_months', '*'],
            ],
            31557660 => [ //'about 1 year',       'about 1 year'),            // 525961*60 seconds
                ['include_utils', 'about_1_year'],
                ['include_utils', 'about_1_year'],
            ],
            63115200 => [ //'over 2 years',       'over 2 years'),            // 1051920*60 seconds
                ['include_utils', 'over_X_years', '*'],
                ['include_utils', 'over_X_years', '*'],
            ],
        ];
        foreach ($expected as $distance => $e) {
            $GLOBALS['Language']->expects(self::atLeast(2))->method('getText');
            DateHelper::distanceOfTimeInWords($_SERVER['REQUEST_TIME'] - $distance, $_SERVER['REQUEST_TIME']);
            DateHelper::distanceOfTimeInWords($_SERVER['REQUEST_TIME'] - $distance, $_SERVER['REQUEST_TIME'], true);
        }
    }

    public function testFormatDateFormatsTheDateAccordingToLanguage(): void
    {
        self::assertMatchesRegularExpression('/2011-\d+-\d+/', $this->formatDate(true, 'Y-m-d'));
        self::assertMatchesRegularExpression('/2011\/\d+\/\d+/', $this->formatDate(true, "Y/d/m"));
    }

    public function testFormatDateCanReturnTheTimeAsWell(): void
    {
        self::assertMatchesRegularExpression('/2011-\d+-\d+ \d+:\d+/', $this->formatDate(false, "Y-m-d h:i"));
    }

    public function testFormatDateReturnsEmptyStringWhenDateIsZero(): void
    {
        $lang = $this->createMock(\BaseLanguage::class);
        $lang->method('getText')->willReturn('Y-m-d');
        self::assertEquals("", DateHelper::formatForLanguage($lang, 0, false));
    }

    private function formatDate(bool $dayOnly, string $format): string
    {
        $lang = $this->createMock(\BaseLanguage::class);
        $lang->method('getText')->willReturn($format);
        $firstOfDecember2011_12_01 = 1322752769;

        return DateHelper::formatForLanguage($lang, $firstOfDecember2011_12_01, $dayOnly);
    }

    public function testDateInPast(): void
    {
        $GLOBALS['Language']
            ->expects(self::once())
            ->method('getText')
            ->with(
                'include_utils',
                'X_minutes',
                8
            )
            ->willReturn('8 minutes');

        self::assertEquals(
            '8 minutes ago',
            DateHelper::timeAgoInWords($_SERVER['REQUEST_TIME'] - 500)
        );
    }

    public function testDateInFuture(): void
    {
        $GLOBALS['Language']
            ->expects(self::once())
            ->method('getText')
            ->with(
                'include_utils',
                'X_minutes',
                8
            )
            ->willReturn('8 minutes');

        self::assertEquals(
            'in 8 minutes',
            DateHelper::timeAgoInWords($_SERVER['REQUEST_TIME'] + 500)
        );
    }

    public function testRelativeDate(): void
    {
        $GLOBALS['Language']
            ->method('getText')
            ->with(
                'system',
                'datefmt',
            )
            ->willReturn('Y-m-d H:i');

        $user = UserTestBuilder::aUser()->withLocale('en_US')->build();

        self::assertEquals(
            '<tlp-relative-date
            date="2009-02-14T00:31:30+01:00"
            absolute-date="2009-02-14 00:31"
            preference="relative"
            locale="en_US"
            placement="tooltip">2009-02-14 00:31</tlp-relative-date>',
            DateHelper::relativeDateInlineContext(1234567890, $user)
        );
    }

    public function testRelativeDateRelativeFistAbsoluteShown(): void
    {
        $GLOBALS['Language']
            ->method('getText')
            ->with(
                'system',
                'datefmt',
            )
            ->willReturn('Y-m-d H:i');

        $user = UserTestBuilder::anActiveUser()
            ->withLocale('en_US')
            ->build();
        $user->setPreference(\DateHelper::PREFERENCE_NAME, \DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN);

        self::assertEquals(
            '<tlp-relative-date
            date="2009-02-14T00:31:30+01:00"
            absolute-date="2009-02-14 00:31"
            preference="relative"
            locale="en_US"
            placement="right">2009-02-14 00:31</tlp-relative-date>',
            DateHelper::relativeDateInlineContext(1234567890, $user)
        );
    }

    public function testRelativeDateRelativeFistAbsoluteShownWithoutTime(): void
    {
        $GLOBALS['Language']
            ->method('getText')
            ->with(
                'system',
                'datefmt_short',
            )
            ->willReturn('Y-m-d');

        $user = UserTestBuilder::anActiveUser()
            ->withLocale('en_US')
            ->build();
        $user->setPreference(\DateHelper::PREFERENCE_NAME, \DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN);

        self::assertEquals(
            '<tlp-relative-date
            date="2009-02-14T00:31:30+01:00"
            absolute-date="2009-02-14"
            preference="relative"
            locale="en_US"
            placement="right">2009-02-14</tlp-relative-date>',
            DateHelper::relativeDateInlineContextWithoutTime(1234567890, $user)
        );
    }

    public function testRelativeDateRelativeFistAbsoluteTooltip(): void
    {
        $GLOBALS['Language']
            ->method('getText')
            ->with(
                'system',
                'datefmt',
            )
            ->willReturn('Y-m-d H:i');

        $user = UserTestBuilder::anActiveUser()
            ->withLocale('en_US')
            ->build();
        $user->setPreference(\DateHelper::PREFERENCE_NAME, \DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP);

        self::assertEquals(
            '<tlp-relative-date
            date="2009-02-14T00:31:30+01:00"
            absolute-date="2009-02-14 00:31"
            preference="relative"
            locale="en_US"
            placement="tooltip">2009-02-14 00:31</tlp-relative-date>',
            DateHelper::relativeDateInlineContext(1234567890, $user)
        );
    }

    public function testRelativeDateAbsoluteFistRelativeShown(): void
    {
        $GLOBALS['Language']
            ->method('getText')
            ->with(
                'system',
                'datefmt',
            )
            ->willReturn('Y-m-d H:i');

        $user = UserTestBuilder::anActiveUser()
            ->withLocale('en_US')
            ->build();
        $user->setPreference(\DateHelper::PREFERENCE_NAME, \DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN);

        self::assertEquals(
            '<tlp-relative-date
            date="2009-02-14T00:31:30+01:00"
            absolute-date="2009-02-14 00:31"
            preference="absolute"
            locale="en_US"
            placement="right">2009-02-14 00:31</tlp-relative-date>',
            DateHelper::relativeDateInlineContext(1234567890, $user)
        );
    }

    public function testRelativeDateAbsoluteFistRelativeTooltip(): void
    {
        $GLOBALS['Language']
            ->method('getText')
            ->with(
                'system',
                'datefmt',
            )
            ->willReturn('Y-m-d H:i');

        $user = UserTestBuilder::anActiveUser()
            ->withLocale('en_US')
            ->build();
        $user->setPreference(\DateHelper::PREFERENCE_NAME, \DateHelper::PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP);

        self::assertEquals(
            '<tlp-relative-date
            date="2009-02-14T00:31:30+01:00"
            absolute-date="2009-02-14 00:31"
            preference="absolute"
            locale="en_US"
            placement="tooltip">2009-02-14 00:31</tlp-relative-date>',
            DateHelper::relativeDateInlineContext(1234567890, $user)
        );
    }

    public function testItBuildsATlpRelativeDateComponentWithDefaultDisplaySetBySiteAdmin(): void
    {
        $GLOBALS['Language']
            ->method('getText')
            ->with(
                'system',
                'datefmt',
            )
            ->willReturn('Y-m-d H:i');

        $user = UserTestBuilder::anActiveUser()
            ->withLocale('en_US')
            ->build();

        \ForgeConfig::set(
            DefaultRelativeDatesDisplayPreferenceRetriever::DEFAULT_RELATIVE_DATES_DISPLAY,
            \DateHelper::PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN
        );

        $this->assertEquals(
            '<tlp-relative-date
            date="2009-02-14T00:31:30+01:00"
            absolute-date="2009-02-14 00:31"
            preference="relative"
            locale="en_US"
            placement="right">2009-02-14 00:31</tlp-relative-date>',
            DateHelper::relativeDateInlineContext(1234567890, $user)
        );
    }
}
