<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\FRS;

class UploadedLinkDeletor
{
    /**
     * @var UploadedLinksDao
     */
    private $uploaded_links_dao;
    /**
     * @var \FRSLog
     */
    private $frs_log;

    public function __construct(UploadedLinksDao $uploaded_links_dao, \FRSLog $frs_log)
    {
        $this->uploaded_links_dao = $uploaded_links_dao;
        $this->frs_log            = $frs_log;
    }

    public function deleteByRelease(\FRSRelease $release, \PFUser $requester)
    {
        $links = $this->uploaded_links_dao->searchLinks($release->getReleaseID());
        if ($links === false) {
            return;
        }
        foreach ($links as $link) {
            $this->frs_log->addLog($requester->getId(), $release->getProject()->getID(), $link['id'], UploadedLink::EVENT_DELETE);
        }

        $this->uploaded_links_dao->markAsDeletedByReleaseId($release->getReleaseID());
    }

    public function deleteByIDsAndRelease(array $links_id, \FRSRelease $release, \PFUser $requester)
    {
        $links = $this->uploaded_links_dao->searchLinksByIdsAndReleaseId($links_id, $release->getReleaseID());
        if ($links === false) {
            return;
        }
        foreach ($links as $link) {
            $this->frs_log->addLog($requester->getId(), $release->getProject()->getID(), $link['id'], UploadedLink::EVENT_DELETE);
        }

        $this->uploaded_links_dao->markAsDeletedByIdsAndReleaseId($links_id, $release->getReleaseID());
    }
}
