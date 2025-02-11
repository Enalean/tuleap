<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\User;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\StoreUserPreferenceStub;

final class NotificationOnAllUpdatesSaverTest extends TestCase
{
    private const USER_ID = 164;
    private StoreUserPreferenceStub $store_preference;

    protected function setUp(): void
    {
        $this->store_preference = new StoreUserPreferenceStub();
    }

    private function save(NotificationOnAllUpdatesPreference $preference): bool
    {
        $saver = new NotificationOnAllUpdatesSaver(
            new NotificationOnAllUpdatesRetriever($this->store_preference),
            $this->store_preference
        );
        return $saver->save($preference, UserTestBuilder::buildWithId(self::USER_ID));
    }

    public static function generateChanges(): iterable
    {
        yield 'Enabled → Disabled' => [true, false, true, NotificationOnAllUpdatesSaver::VALUE_NO_NOTIF];
        yield 'Disabled → Enabled' => [false, true, true, NotificationOnAllUpdatesSaver::VALUE_NOTIF];
        yield 'Disabled → Disabled' => [false, false, false, NotificationOnAllUpdatesSaver::VALUE_NO_NOTIF];
        yield 'Enabled → Enabled' => [true, true, false, NotificationOnAllUpdatesSaver::VALUE_NOTIF];
    }

    /**
     * @dataProvider generateChanges
     */
    public function testItHandlesChanges(
        bool $stored_preference,
        bool $new_preference,
        bool $expected_change,
        string $expected_new_stored_value,
    ): void {
        $stored_value = $stored_preference ? NotificationOnAllUpdatesSaver::VALUE_NOTIF : NotificationOnAllUpdatesSaver::VALUE_NO_NOTIF;
        $this->store_preference->set(self::USER_ID, NotificationOnAllUpdatesSaver::PREFERENCE_NAME, $stored_value);

        $changed = $this->save(new NotificationOnAllUpdatesPreference($new_preference));
        self::assertSame($expected_change, $changed);
        $row = $this->store_preference->search(self::USER_ID, NotificationOnAllUpdatesSaver::PREFERENCE_NAME);
        self::assertSame($expected_new_stored_value, $row['preference_value']);
    }

    public function testItDefaultsToEnabledWhenNothingIsSaved(): void
    {
        $changed = $this->save(new NotificationOnAllUpdatesPreference(true));
        self::assertFalse($changed);
        self::assertEmpty(
            $this->store_preference->search(self::USER_ID, NotificationOnAllUpdatesSaver::PREFERENCE_NAME)
        );
    }
}
