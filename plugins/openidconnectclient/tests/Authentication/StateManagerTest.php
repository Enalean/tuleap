<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

use Tuleap\OpenIDConnectClient\Authentication\SessionState;
use Tuleap\OpenIDConnectClient\Authentication\StateManager;
use Tuleap\OpenIDConnectClient\Authentication\State;

require_once(__DIR__ . '/../bootstrap.php');

class StateManagerTest extends TuleapTestCase
{
    public function itValidatesValidState() {
        $key           = 'Tuleap_key';
        $return_to     = '/return_to';
        $nonce         = 'random_string';
        $state_factory = mock('Tuleap\OpenIDConnectClient\Authentication\StateFactory');
        $state_storage = mock('Tuleap\OpenIDConnectClient\Authentication\StateStorage');
        $state         = new State(1234, $return_to, $key, $nonce);
        $signed_state  = $state->getSignedState();
        $stored_state  = new SessionState($key, $return_to, $nonce);
        $state_storage->setReturnValue('loadState', $stored_state);

        $state_manager = new StateManager($state_storage, $state_factory);
        $state_manager->validateState($signed_state);
    }

    public function itDoesNotValidateInvalidState() {
        $return_to     = '/return_to';
        $nonce         = 'random_string';
        $state_factory = mock('Tuleap\OpenIDConnectClient\Authentication\StateFactory');
        $state_storage = mock('Tuleap\OpenIDConnectClient\Authentication\StateStorage');
        $state         = new State(1234, $return_to, 'key1', $nonce);
        $signed_state  = $state->getSignedState();
        $stored_state  = new SessionState('key2', $return_to, $nonce);
        $state_storage->setReturnValue('loadState', $stored_state);

        $state_manager = new StateManager($state_storage, $state_factory);
        $this->expectException(State\StateMismatchException::class);
        $state_manager->validateState($signed_state);
    }

    public function itDoesNotTryToValidateInvalidStoredStateHash() {
        $state_factory = mock('Tuleap\OpenIDConnectClient\Authentication\StateFactory');
        $state_storage = mock('Tuleap\OpenIDConnectClient\Authentication\StateStorage');
        $state_storage->setReturnValue('loadState', null);

        $state_manager = new StateManager($state_storage, $state_factory);
        $this->expectException(State\InvalidLocalStateException::class);
        $state_manager->validateState('signed_state');
    }

    public function itDoesNotTryToValidateMissingStateHash() {
        $state_factory = mock('Tuleap\OpenIDConnectClient\Authentication\StateFactory');
        $state_storage = mock('Tuleap\OpenIDConnectClient\Authentication\StateStorage');
        $state_storage->setReturnValue('loadState', 'stored_state_hash');

        $state_manager = new StateManager($state_storage, $state_factory);
        $this->expectException(State\InvalidRemoteStateException::class);
        $state_manager->validateState(null);
    }
}