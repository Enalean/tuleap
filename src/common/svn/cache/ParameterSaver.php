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

namespace Tuleap\SvnCore\Cache;

use Event;
use EventManager;
use Valid_UInt;

class ParameterSaver
{
    /**
     * @var ParameterDao
     */
    private $dao;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(ParameterDao $dao, EventManager $event_manager)
    {
        $this->dao           = $dao;
        $this->event_manager = $event_manager;
    }

    /**
     * @throws ParameterDataAccessException
     * @throws ParameterMalformedDataException
     */
    public function save($maximum_credentials, $lifetime)
    {
        if (! $this->areParametersValid($maximum_credentials, $lifetime)) {
            throw new ParameterMalformedDataException();
        }

        $is_saved = $this->dao->save($maximum_credentials, $lifetime);
        if ($is_saved === false) {
            throw new ParameterDataAccessException();
        }

        $this->event_manager->processEvent(Event::SVN_AUTH_CACHE_CHANGE, []);
    }

    /**
     * @return bool
     */
    private function areParametersValid($maximum_credentials, $lifetime)
    {
        $unsigned_integer_validator = new Valid_UInt();
        $unsigned_integer_validator->required();

        return $unsigned_integer_validator->validate($maximum_credentials) &&
            $unsigned_integer_validator->validate($lifetime);
    }
}
