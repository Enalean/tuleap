<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StateManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItValidatesValidState(): void
    {
        $this->expectNotToPerformAssertions();

        $key                = str_repeat('a', 32);
        $return_to          = '/return_to';
        $nonce              = 'random_string';
        $pkce_code_verifier = new ConcealedString('code_verifier');
        $state_factory      = $this->createMock(\Tuleap\OpenIDConnectClient\Authentication\StateFactory::class);
        $state_storage      = $this->createMock(\Tuleap\OpenIDConnectClient\Authentication\StateStorage::class);
        $state              = new State(1234, $return_to, $key, $nonce, $pkce_code_verifier);
        $signed_state       = $state->getSignedState();
        $stored_state       = new SessionState($key, $return_to, $nonce, $pkce_code_verifier);
        $state_storage->method('loadState')->willReturn($stored_state);

        $state_manager = new StateManager($state_storage, $state_factory);
        $state_manager->validateState($signed_state);
    }

    public function testItDoesNotValidateInvalidState(): void
    {
        $return_to          = '/return_to';
        $nonce              = 'random_string';
        $pkce_code_verifier = new ConcealedString('code_verifier');
        $state_factory      = $this->createMock(\Tuleap\OpenIDConnectClient\Authentication\StateFactory::class);
        $state_storage      = $this->createMock(\Tuleap\OpenIDConnectClient\Authentication\StateStorage::class);
        $state              = new State(1234, $return_to, str_repeat('a', 32), $nonce, $pkce_code_verifier);
        $signed_state       = $state->getSignedState();
        $stored_state       = new SessionState(str_repeat('b', 32), $return_to, $nonce, $pkce_code_verifier);
        $state_storage->method('loadState')->willReturn($stored_state);

        $state_manager = new StateManager($state_storage, $state_factory);
        $this->expectException(State\StateMismatchException::class);
        $state_manager->validateState($signed_state);
    }

    public function testItDoesNotTryToValidateInvalidStoredStateHash(): void
    {
        $state_factory = $this->createMock(\Tuleap\OpenIDConnectClient\Authentication\StateFactory::class);
        $state_storage = $this->createMock(\Tuleap\OpenIDConnectClient\Authentication\StateStorage::class);
        $state_storage->method('loadState')->willReturn(null);

        $state_manager = new StateManager($state_storage, $state_factory);
        $this->expectException(State\InvalidLocalStateException::class);
        $state_manager->validateState('signed_state');
    }

    public function testItDoesNotTryToValidateMissingStateHash(): void
    {
        $state_factory = $this->createMock(\Tuleap\OpenIDConnectClient\Authentication\StateFactory::class);
        $state_storage = $this->createMock(\Tuleap\OpenIDConnectClient\Authentication\StateStorage::class);
        $state_storage->method('loadState')->willReturn('stored_state_hash');

        $state_manager = new StateManager($state_storage, $state_factory);
        $this->expectException(State\InvalidRemoteStateException::class);
        $state_manager->validateState(null);
    }
}
