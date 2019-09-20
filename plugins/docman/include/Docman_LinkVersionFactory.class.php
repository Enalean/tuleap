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

/**
 * VersionFactory is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_LinkVersionFactory
{

    /**
     * @var Docman_LinkVersionDao
     */
    private $dao;

    public function __construct()
    {
        $this->dao = new Docman_LinkVersionDao();
    }

    /**
     * @return Docman_LinkVersion[]
     */
    public function getAllVersionForItem(Docman_Link $item)
    {
        $versions = array();
        $rows     = $this->dao->searchByItemId($item->getId());

        foreach ($rows as $row) {
            $versions[] = new Docman_LinkVersion($row);
        }

        return $versions;
    }

    /**
     * @return Docman_LinkVersion|null
     */
    public function getSpecificVersion(Docman_Link $item, $number)
    {
        $row = $this->dao->searchByNumber($item->getId(), $number)->getRow();
        if (! $row) {
            return null;
        }

        return new Docman_LinkVersion($row);
    }

    public function create(Docman_Link $link, $label, $changelog, $date)
    {
        return $this->dao->createNewLinkVersion($link, $label, $changelog, $date);
    }

    public function createLinkWithSpecificVersion(Docman_Link $link, string $label, string $changelog, int $date, int $version)
    {
        return $this->dao->createLinkWithSpecificVersion($link, $label, $changelog, $date, $version);
    }

    /**
     * @return Docman_LinkVersion|null
     */
    public function getLatestVersion(Docman_Link $link)
    {
        $row = $this->dao->searchByItemId($link->getId())->getRow();

        if (! $row) {
            return null;
        }
        return new Docman_LinkVersion($row);
    }
}
