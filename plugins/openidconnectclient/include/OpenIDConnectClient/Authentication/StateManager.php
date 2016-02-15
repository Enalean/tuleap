<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
use InoOicClient\Oic\Authorization\State\Manager;
use InoOicClient\Oic\Authorization\State\Exception\InvalidLocalStateException;
use InoOicClient\Oic\Authorization\State\Exception\InvalidRemoteStateException;
use InoOicClient\Oic\Authorization\State\Exception\StateMismatchException;
use Tuleap\OpenIDConnectClient\Provider\Provider;

class StateManager extends Manager {

    public function __construct(StateStorage $state_storage, StateFactory $state_factory) {
        parent::__construct($state_storage, $state_factory);
    }

    /**
     * @return State
     * @throws InvalidLocalStateException
     * @throws InvalidRemoteStateException
     * @throws StateMismatchException
     */
    public function validateState($signed_state) {
        $stored_state = $this->getStorage()->loadState();

        if($stored_state === null && $signed_state !== null) {
            throw new InvalidLocalStateException('Invalid stored state hash - empty string');
        }

        if($stored_state !== null && $signed_state === null) {
            throw new InvalidRemoteStateException("The server did not return a state hash");
        }

        if($stored_state !== null && $signed_state !== null) {
            try {
                return State::createFromSignature(
                    $signed_state,
                    $stored_state->getReturnTo(),
                    $stored_state->getSecretKey()
                );
            } catch (Exception $ex) {
                throw new StateMismatchException('Invalid state hash returned from server');
            }
        }
    }

    /**
     * @return State
     */
    public function initState(Provider $provider, $return_to) {
        $state = $this->getFactory()->createState($provider->getId(), $return_to);
        $this->getStorage()->saveState($state);

        return $state;
    }

    public function clearState() {
        $this->getStorage()->clear();
    }
}