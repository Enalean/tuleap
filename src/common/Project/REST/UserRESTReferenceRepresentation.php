<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Project\REST;

class UserRESTReferenceRepresentation
{
    /**
     * @var int {@required false}
     */
    public $id;
    /**
     * @var string {@required false}
     */
    public $username;
    /**
     * @var string {@required false}
     */
    public $email;
    /**
     * @var string {@required false}
     */
    public $ldap_id;

    /**
     * @return self
     */
    public static function buildFromArray(array $data)
    {
        $representation = new self();
        /** @psalm-suppress RawObjectIteration */
        foreach ($representation as $key => $value) {
            if (isset($data[$key])) {
                $representation->$key = $data[$key];
            }
        }
        return $representation;
    }

    public function __toString()
    {
        return (string) print_r($this, true);
    }
}
