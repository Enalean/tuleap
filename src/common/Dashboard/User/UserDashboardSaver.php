<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\User;

use PFUser;
use Tuleap\Dashboard\NameDashboardAlreadyExistsException;
use Tuleap\Dashboard\NameDashboardDoesNotExistException;

class UserDashboardSaver
{
    /**
     * @var UserDashboardDao
     */
    private $dao;

    public function __construct(UserDashboardDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param $name
     * @return bool
     * @throws NameDashboardAlreadyExistsException
     * @throws NameDashboardDoesNotExistException
     */
    public function save(PFUser $user, $name)
    {
        if (! $name) {
            throw new NameDashboardDoesNotExistException();
        }

        if ($this->dao->searchByUserIdAndName($user, $name)->count() > 0) {
            throw new NameDashboardAlreadyExistsException();
        }
        return $this->dao->save($user, $name);
    }
}
