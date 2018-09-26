<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

/**
 * First class collection of users
 */
class Users {

    /** @var LegacyDataAccessResultInterface */
    private $dar;

    public function __construct(LegacyDataAccessResultInterface $dar = null) {
        $this->dar = $dar;
    }

    public function getDar() {
        return $this->dar;
    }

    public function reify() {
        $result = array();
        foreach ($this->dar as $row) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * 
     * @return array
     */
    public function getNames() {
        $result = array();
        foreach ($this->dar as $user) {
            $result[] = $user->getUserName();
        }
        return $result;
    }
}
?>
