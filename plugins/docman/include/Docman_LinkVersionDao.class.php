<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Docman_LinkVersionDao extends DataAccessObject
{

    /**
     * @return DataAccessResult
     */
    public function searchByItemId($item_id)
    {
        $item_id = $this->da->escapeInt($item_id);

        $sql = "SELECT * FROM plugin_docman_link_version
                WHERE item_id = $item_id
                ORDER BY number DESC";

        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult
     */
    public function searchByNumber($item_id, $number)
    {
        $item_id = $this->da->escapeInt($item_id);
        $number  = $this->da->escapeInt($number);

        $sql = "SELECT * FROM plugin_docman_link_version
                WHERE item_id = $item_id
                  AND  number = $number";

        return $this->retrieve($sql);
    }

    public function createNewLinkVersion(Docman_Link $link, $label, $changelog, $date)
    {
        $label     = $this->da->quoteSmart($label);
        $changelog = $this->da->quoteSmart($changelog);
        $date      = $this->da->escapeInt($date);
        $link_url  = $this->da->quoteSmart($link->getUrl());
        $item_id   = $this->da->escapeInt($link->getId());
        $user_id   = $this->da->escapeInt($link->getOwnerId());

        $sql = "INSERT INTO plugin_docman_link_version (item_id, number, user_id, label, changelog, date, link_url)
                SELECT $item_id, IFNULL(MAX(number), 0) + 1 as number, $user_id, $label, $changelog, $date, $link_url
                FROM plugin_docman_link_version
                WHERE item_id = $item_id";

        return $this->update($sql);
    }

    public function createLinkWithSpecificVersion(Docman_Link $link, $label, $changelog, $date, $version)
    {
        $label     = $this->da->quoteSmart($label);
        $changelog = $this->da->quoteSmart($changelog);
        $date      = $this->da->escapeInt($date);
        $version   = $this->da->escapeInt($version);
        $link_url  = $this->da->quoteSmart($link->getUrl());
        $item_id   = $this->da->escapeInt($link->getId());
        $user_id   = $this->da->escapeInt($link->getOwnerId());

        $sql = "INSERT INTO plugin_docman_link_version (id, item_id, number, user_id, label, changelog, date, link_url) VALUES
               ($version, $item_id, $version, $user_id, $label, $changelog, $date, $link_url)";

        return $this->update($sql);
    }
}
