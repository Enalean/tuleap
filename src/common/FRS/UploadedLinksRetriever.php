<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

namespace Tuleap\FRS;

class UploadedLinksRetriever
{
    /**
     * @var UploadedLinksDao
     */
    private $dao;
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct(UploadedLinksDao $dao, \UserManager $user_manager)
    {
        $this->dao          = $dao;
        $this->user_manager = $user_manager;
    }

    /**
     *
     * @return UploadedLink[]
     */
    public function getLinksForRelease(\FRSRelease $release)
    {
        $links = array();

        foreach ($this->dao->searchLinks($release->getReleaseID()) as $row) {
            $links[] = $this->instantiateRow($row);
        }

        return $links;
    }

    private function instantiateRow(array $row)
    {
        return new UploadedLink(
            $row['id'],
            $this->user_manager->getUserById($row['owner_id']),
            $row['link'],
            $row['name'],
            $row['release_time']
        );
    }
}
