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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../bootstrap.php');

class StateManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItValidatesValidState(): void
    {
        $key           = 'Tuleap_key';
        $return_to     = '/return_to';
        $nonce         = 'random_string';
        $state_factory = \Mockery::spy(\Tuleap\OpenIDConnectClient\Authentication\StateFactory::class);
        $state_storage = \Mockery::spy(\Tuleap\OpenIDConnectClient\Authentication\StateStorage::class);
        $state         = new State(1234, $return_to, $key, $nonce);
        $signed_state  = $state->getSignedState();
        $stored_state  = new SessionState($key, $return_to, $nonce);
        $state_storage->shouldReceive('loadState')->andReturns($stored_state);

        $state_manager = new StateManager($state_storage, $state_factory);
        $state_manager->validateState($signed_state);
    }

    public function testItDoesNotValidateInvalidState(): void
    {
        $return_to     = '/return_to';
        $nonce         = 'random_string';
        $state_factory = \Mockery::spy(\Tuleap\OpenIDConnectClient\Authentication\StateFactory::class);
        $state_storage = \Mockery::spy(\Tuleap\OpenIDConnectClient\Authentication\StateStorage::class);
        $state         = new State(1234, $return_to, 'key1', $nonce);
        $signed_state  = $state->getSignedState();
        $stored_state  = new SessionState('key2', $return_to, $nonce);
        $state_storage->shouldReceive('loadState')->andReturns($stored_state);

        $state_manager = new StateManager($state_storage, $state_factory);
        $this->expectException(State\StateMismatchException::class);
        $state_manager->validateState($signed_state);
    }

    public function testItDoesNotTryToValidateInvalidStoredStateHash(): void
    {
        $state_factory = \Mockery::spy(\Tuleap\OpenIDConnectClient\Authentication\StateFactory::class);
        $state_storage = \Mockery::spy(\Tuleap\OpenIDConnectClient\Authentication\StateStorage::class);
        $state_storage->shouldReceive('loadState')->andReturns(null);

        $state_manager = new StateManager($state_storage, $state_factory);
        $this->expectException(State\InvalidLocalStateException::class);
        $state_manager->validateState('signed_state');
    }

    public function testItDoesNotTryToValidateMissingStateHash(): void
    {
        $state_factory = \Mockery::spy(\Tuleap\OpenIDConnectClient\Authentication\StateFactory::class);
        $state_storage = \Mockery::spy(\Tuleap\OpenIDConnectClient\Authentication\StateStorage::class);
        $state_storage->shouldReceive('loadState')->andReturns('stored_state_hash');

        $state_manager = new StateManager($state_storage, $state_factory);
        $this->expectException(State\InvalidRemoteStateException::class);
        $state_manager->validateState(null);
    }
}
