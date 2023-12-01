<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication;

use Exception;
use Tuleap\OpenIDConnectClient\Authentication\State\InvalidLocalStateException;
use Tuleap\OpenIDConnectClient\Authentication\State\InvalidRemoteStateException;
use Tuleap\OpenIDConnectClient\Authentication\State\StateMismatchException;
use Tuleap\OpenIDConnectClient\Provider\Provider;

class StateManager
{
    /**
     * @var StateStorage
     */
    private $state_storage;
    /**
     * @var StateFactory
     */
    private $state_factory;

    public function __construct(StateStorage $state_storage, StateFactory $state_factory)
    {
        $this->state_storage = $state_storage;
        $this->state_factory = $state_factory;
    }

    /**
     * @return State
     * @throws InvalidLocalStateException
     * @throws InvalidRemoteStateException
     * @throws StateMismatchException
     */
    public function validateState($signed_state)
    {
        $stored_state = $this->state_storage->loadState();

        if ($stored_state === null && $signed_state !== null) {
            throw new InvalidLocalStateException('Invalid stored state hash - empty string');
        }

        if ($stored_state !== null && ($signed_state === null || $signed_state === '')) {
            throw new InvalidRemoteStateException('The server did not return a state hash');
        }

        if ($stored_state !== null && $signed_state !== null && $signed_state !== '') {
            try {
                return State::createFromSignature(
                    $signed_state,
                    $stored_state->getReturnTo(),
                    $stored_state->getSecretKey(),
                    $stored_state->getNonce(),
                    $stored_state->getPKCECodeVerifier()
                );
            } catch (Exception $ex) {
                throw new StateMismatchException('Invalid state hash returned from server');
            }
        }
    }

    public function initState(Provider $provider, ?string $return_to): State
    {
        $state = $this->state_factory->createState($provider->getId(), $return_to);
        $this->state_storage->saveState($state);

        return $state;
    }

    public function clearState()
    {
        $this->state_storage->clear();
    }
}
