<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 *   This file is a part of Tuleap.
 *
 *   Tuleap is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *   Tuleap is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */


namespace Tuleap\FRS;

use FRSRelease;
use PFUser;

class UploadedLinksUpdater
{
    /**
     * @var UploadedLinksDao
     */
    private $dao;
    /**
     * @var \FRSLog
     */
    private $frs_log;

    public function __construct(UploadedLinksDao $dao, \FRSLog $frs_log)
    {
        $this->dao     = $dao;
        $this->frs_log = $frs_log;
    }

    public function update(array $release_links, PFUser $user, FRSRelease $release, $release_time)
    {
        foreach ($release_links as $link) {
            $id = $this->dao->create(
                $link['name'],
                $link['link'],
                $user->getId(),
                $release->getReleaseID(),
                $release_time
            );
            $this->frs_log->addLog($user->getId(), $release->getProject()->getID(), $id, UploadedLink::EVENT_CREATE);
        }
    }
}
