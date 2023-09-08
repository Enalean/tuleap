<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tuleap\Cryptography\ConcealedString;

class DynamicUser extends \PFUser
{
    public const ID = 80;

    /**
     * @var bool
     */
    private $is_logged_in;

    public function __construct($realname, array $row, $is_logged_in)
    {
        parent::__construct($row);
        $this->is_logged_in = $is_logged_in;
        $this->realname     = $realname;
    }

    public function getStatus()
    {
        if ($this->is_logged_in) {
            return self::STATUS_ACTIVE;
        }
        return parent::getStatus();
    }

    public function isSuperUser(): bool
    {
        return true;
    }

    public function setPassword(ConcealedString $password): void
    {
    }

    /**
     * @param string $name
     */
    public function setUserName($name)
    {
    }

    public function setStatus($status)
    {
    }

    public function setExpiryDate($expiryDate)
    {
    }
}
