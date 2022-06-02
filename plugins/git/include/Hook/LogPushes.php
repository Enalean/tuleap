<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook;

use GitDao;

/**
 * Store push information in the database
 */
class LogPushes
{
    /** @var GitDao */
    private $dao;

    public function __construct(GitDao $dao)
    {
        $this->dao = $dao;
    }

    public function executeForRepository(PushDetails $push_details): void
    {
        $this->dao->logGitPush(
            $push_details->getRepository()->getId(),
            $push_details->getUser()->getId(),
            $_SERVER['REQUEST_TIME'],
            count($push_details->getRevisionList()),
            $push_details->getRefname(),
            $push_details->getType(),
            $push_details->getRefnameType()
        );
    }
}
