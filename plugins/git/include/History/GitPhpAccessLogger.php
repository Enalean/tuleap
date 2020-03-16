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

namespace Tuleap\Git\History;

use GitRepository;
use PFUser;
use DateTime;

class GitPhpAccessLogger
{
    private $dao;

    public function __construct(Dao $dao)
    {
        $this->dao = $dao;
    }

    public function logAccess(GitRepository $repository, PFUser $user)
    {
        $request_time = new DateTime('@' . $_SERVER['REQUEST_TIME']);
        $day          = $request_time->format('Ymd');

        $this->dao->addGitReadAccess($day, $repository->getId(), $user->getId(), 1);
    }
}
