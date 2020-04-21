<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Language;

use PHPUnit\Framework\TestCase;

final class LocaleSwitcherTest extends TestCase
{
    public function testCanSetLocaleForSpecificExecutionContext(): void
    {
        $locale_switcher = new LocaleSwitcher();

        $current_locale_before_context_switch = setlocale(LC_MESSAGES, '0');

        $locale_execution_context = '';

        $locale_switcher->setLocaleForSpecificExecutionContext(
            'en_GB',
            static function () use (&$locale_execution_context) {
                $locale_execution_context = setlocale(LC_MESSAGES, '0');
            }
        );

        $this->assertNotEquals($current_locale_before_context_switch, $locale_execution_context);
        $this->assertEquals($current_locale_before_context_switch, setlocale(LC_MESSAGES, '0'));
        $this->assertEquals('en_GB.UTF-8', $locale_execution_context);
    }

    public function testCanSetLocaleForSpecificExecutionContextAndRestoreInitialContextEvenIfSomethingIsThrown(): void
    {
        $locale_switcher = new LocaleSwitcher();

        $current_locale_before_context_switch = setlocale(LC_MESSAGES, '0');

        try {
            $locale_switcher->setLocaleForSpecificExecutionContext(
                'en_GB',
                static function () {
                    throw new \RuntimeException();
                }
            );
        } catch (\RuntimeException $ex) {
        }

        $this->assertEquals($current_locale_before_context_switch, setlocale(LC_MESSAGES, '0'));
    }
}
