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

namespace Tuleap\Bugzilla\Reference;

class ReferenceRetriever
{
    /**
     * @var Dao
     */
    private $dao;

    public function __construct(Dao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return array of Reference
     */
    public function getAllReferences()
    {
        $references = array();

        foreach ($this->dao->searchAllReferences() as $row) {
            $references[] = $this->instantiateFromRow($row);
        }

        return $references;
    }

    private function instantiateFromRow(array $references)
    {
        return new Reference(
            $references['id'],
            $references['keyword'],
            $references['server'],
            $references['username'],
            $references['api_key'],
            $references['are_followup_private']
        );
    }

    public function getReferenceByKeyword($keyword)
    {
        $row = $this->dao->searchReferenceByKeyword($keyword);
        if (empty($row)) {
            return null;
        }

        return $this->instantiateFromRow($row);
    }
}
