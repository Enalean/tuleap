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

namespace Tuleap\PullRequest;

class Factory {

    /**
     * @var PullRequest\Dao
     */
    private $dao;

    public function __construct(Dao $dao) {
        $this->dao = $dao;
    }

    /**
     * @return PullRequest
     */
    public function getPullRequestById($id) {
        $row = $this->dao->searchByPullRequestId($id)->getRow();
        if ($row === false) {
            throw new PullRequestNotFoundException();
        }

        return new PullRequest(
            $row['id'],
            $row['repository_id'],
            $row['user_id'],
            $row['creation_date'],
            $row['branch_src'],
            $row['sha1_src'],
            $row['branch_dest'],
            $row['sha1_dest'],
            $row['status']
        );
    }
}
