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

class UploadedLinksDao extends \DataAccessObject
{
    public function searchLinks($release_id)
    {
        $release_id = $this->da->escapeInt($release_id);

        $sql = "SELECT * FROM frs_uploaded_links WHERE release_id = $release_id AND is_deleted = FALSE";

        return $this->retrieve($sql);
    }

    public function searchLinksByIdsAndReleaseId(array $links_id, $release_id)
    {
        $release_id        = $this->da->escapeInt($release_id);
        $imploded_links_id = $this->da->escapeIntImplode($links_id);

        $sql = "SELECT * FROM frs_uploaded_links WHERE release_id = $release_id AND id IN ($imploded_links_id)  AND is_deleted = FALSE";

        return $this->retrieve($sql);
    }

    public function create($name, $link, $user_id, $release_id, $release_time)
    {
        $release_id   = $this->da->escapeInt($release_id);
        $user_id      = $this->da->escapeInt($user_id);
        $release_time = $this->da->escapeInt($release_time);
        $name         = $this->da->quoteSmart($name);
        $link         = $this->da->quoteSmart($link);

        $sql = "INSERT INTO frs_uploaded_links (name, link, owner_id, release_id, release_time)
                  VALUES ($name, $link, $user_id, $release_id, $release_time)";

        return $this->updateAndGetLastId($sql);
    }

    public function markAsDeletedByIdsAndReleaseId(array $links_id, $release_id)
    {
        $release_id        = $this->da->escapeInt($release_id);
        $imploded_links_id = $this->da->escapeIntImplode($links_id);

        $sql = "UPDATE frs_uploaded_links SET is_deleted = TRUE WHERE release_id = $release_id AND id IN ($imploded_links_id)";

        $this->update($sql);
    }

    public function markAsDeletedByReleaseId($release_id)
    {
        $release_id = $this->da->escapeInt($release_id);

        $sql = "UPDATE frs_uploaded_links SET is_deleted = TRUE WHERE release_id = $release_id";

        $this->update($sql);
    }
}
