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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NotificationOnOwnActionRetrieverTest extends TestCase
{
    private const USER_ID = 120;
    private StoreUserPreferenceStub $dao;

    protected function setUp(): void
    {
        $this->dao = new StoreUserPreferenceStub();
    }

    private function retrieve(): NotificationOnOwnActionPreference
    {
        $retriever = new NotificationOnOwnActionRetriever($this->dao);
        return $retriever->retrieve(UserTestBuilder::buildWithId(self::USER_ID));
    }

    public function testItIsOptOutAndDefaultsToEnabledWhenNotStored(): void
    {
        $preference = $this->retrieve();
        self::assertTrue($preference->enabled);
    }

    public function testItRetrievesDisabled(): void
    {
        $this->dao->set(
            self::USER_ID,
            NotificationOnOwnActionSaver::PREFERENCE_NAME,
            NotificationOnOwnActionSaver::VALUE_NO_NOTIF
        );
        $preference = $this->retrieve();
        self::assertFalse($preference->enabled);
    }

    public function testItRetrievesEnabled(): void
    {
        $this->dao->set(
            self::USER_ID,
            NotificationOnOwnActionSaver::PREFERENCE_NAME,
            NotificationOnOwnActionSaver::VALUE_NOTIF
        );
        $preference = $this->retrieve();
        self::assertTrue($preference->enabled);
    }
}
