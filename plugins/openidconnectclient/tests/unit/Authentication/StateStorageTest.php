<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication;

use Tuleap\Cryptography\ConcealedString;

final class StateStorageTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testStoredStateIsRetrieved(): void
    {
        $storage_medium = [];
        $state_storage  = new StateStorage($storage_medium);

        $state = new State(123, 'return_to', 'secret_key', 'nonce', new ConcealedString('pkce_code_verifier'));
        $state_storage->saveState($state);
        $session_state = $state_storage->loadState();

        self::assertNotNull($session_state);
        self::assertSame($state->getSecretKey(), $session_state->getSecretKey());
        self::assertSame($state->getNonce(), $session_state->getNonce());
        self::assertSame($state->getReturnTo(), $session_state->getReturnTo());
    }

    public function testStateStorageCanBeCleared(): void
    {
        $storage_medium = [];
        $state_storage  = new StateStorage($storage_medium);

        $state = new State(123, 'return_to', 'secret_key', 'nonce', new ConcealedString('pkce_code_verifier'));
        $state_storage->saveState($state);

        self::assertNotNull($state_storage->loadState());
        $state_storage->clear();
        self::assertNull($state_storage->loadState());
    }
}
