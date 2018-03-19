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

namespace Tuleap\DynamicCredentials\User;

class DynamicUser extends \PFUser
{
    const ID = 80;

    /**
     * @var bool
     */
    private $is_logged_in;

    public function __construct(array $row, $is_logged_in)
    {
        parent::__construct($row);
        $this->is_logged_in = $is_logged_in;
    }

    public function getStatus()
    {
        if ($this->isLoggedIn()) {
            return self::STATUS_ACTIVE;
        }
        return parent::getStatus();
    }

    public function isSuperUser()
    {
        return true;
    }

    public function isLoggedIn()
    {
        return $this->is_logged_in;
    }

    public function setPassword($password)
    {
    }

    public function setUserName($username)
    {
    }

    public function setStatus($status)
    {
    }

    public function setUnixStatus($unixStatus)
    {
    }

    public function setExpiryDate($expiryDate)
    {
    }
}
