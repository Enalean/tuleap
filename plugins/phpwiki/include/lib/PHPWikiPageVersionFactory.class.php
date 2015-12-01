<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class PHPWikiPageVersionFactory {
    /** @var PHPWikiVersionDao */
    private $wiki_version_dao;

    public function __construct($wiki_version_dao) {
        $this->wiki_version_dao = $wiki_version_dao;
    }

    /** @return PHPWikiPageVersion */
    public function getPageVersion($page_id, $page_version_id) {
        $page_version = null;

        $result = $this->wiki_version_dao->getSpecificVersionForGivenPage($page_id, $page_version_id);
        if ($result && $result->count() > 0) {
            $page_version = $this->getInstanceFromRow($result->getRow());
        }

        return $page_version;
    }

    /** @return array */
    public function getPageAllVersions($page_id) {
        $page_versions = array();

        foreach ($this->wiki_version_dao->getAllVersionForGivenPage($page_id) as $row) {
            $page_versions[] = $this->getInstanceFromRow($row);
        }

        return $page_versions;
    }

    /** @return PHPWikiPageVersion */
    private function getInstanceFromRow($row) {
        return new PHPWikiPageVersion(
            $row['id'],
            $row['version'],
            $row['content']
        );
    }
}