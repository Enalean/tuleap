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
    public const int ID = 80;

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

    #[\Override]
    public function getStatus()
    {
        if ($this->is_logged_in) {
            return self::STATUS_ACTIVE;
        }
        return parent::getStatus();
    }

    #[\Override]
    public function isSuperUser(): bool
    {
        return true;
    }

    #[\Override]
    public function setPassword(ConcealedString $password): void
    {
    }

    /**
     * @param string $name
     */
    #[\Override]
    public function setUserName($name)
    {
    }

    #[\Override]
    public function setStatus($status)
    {
    }

    #[\Override]
    public function setExpiryDate($expiryDate)
    {
    }

    /**
     * All actions done with this user are done by human operators
     * not by internal automatic processes
     */
    #[\Override]
    public function isATechnicalUser(): bool
    {
        return false;
    }
}
