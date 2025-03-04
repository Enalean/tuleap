<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Tracker;

use trackerPlugin;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\Preferences\UserPreferencesGetDefaultValue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerPluginTest extends TestCase
{
    private function getPlugin(): trackerPlugin
    {
        return new trackerPlugin(1);
    }

    public static function provideUserPreferenceValues(): iterable
    {
        yield 'Value is false' => [false, true];
        yield "Value is '0'" => ['0', false];
        yield "Value is '1'" => ['1', false];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideUserPreferenceValues')]
    public function testItSetsADefaultValueForTrackerCommentOrderUserPreference(
        string|false $value,
        bool $should_have_default_value,
    ): void {
        $event = new UserPreferencesGetDefaultValue('tracker_comment_invertorder_949', $value);
        $this->getPlugin()->setDefaultCommentOrderUserPreference($event);
        self::assertSame($should_have_default_value, $event->hasDefaultValue());
        if ($event->hasDefaultValue()) {
            self::assertSame('0', $event->getDefaultValue());
        }
    }

    public function testItDoesNotTouchOtherUserPreferences(): void
    {
        $event = new UserPreferencesGetDefaultValue('some_unrelated_key', 'some_value');
        $this->getPlugin()->setDefaultCommentOrderUserPreference($event);
        self::assertFalse($event->hasDefaultValue());
    }
}
