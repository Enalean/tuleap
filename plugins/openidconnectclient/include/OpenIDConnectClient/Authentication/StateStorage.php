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

namespace Tuleap\OpenIDConnectClient\Authentication;

class StateStorage
{
    public const AUTHORIZATION_STATE = 'tuleap_oidc_authorization_state';

    /**
     * @var array
     */
    private $storage;

    public function __construct(array &$storage)
    {
        $this->storage =& $storage;
    }

    public function saveState(State $state)
    {
        $stored_state = new SessionState(
            $state->getSecretKey(),
            $state->getReturnTo(),
            $state->getNonce()
        );
        $this->storage[self::AUTHORIZATION_STATE] = $stored_state->convertToMinimalRepresentation();
    }

    /**
     * @return null|SessionState
     */
    public function loadState()
    {
        if (! isset($this->storage[self::AUTHORIZATION_STATE])) {
            return null;
        }
        return SessionState::buildFromMinimalRepresentation($this->storage[self::AUTHORIZATION_STATE]);
    }

    public function clear()
    {
        unset($this->storage[self::AUTHORIZATION_STATE]);
    }
}
