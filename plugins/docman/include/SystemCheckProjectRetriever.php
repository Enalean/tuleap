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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Docman_SystemCheckProjectRetriever
{

    /** @var Docman_SystemCheckDao */
    private $dao;

    public function __construct(Docman_SystemCheckDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return array
     */
    public function getActiveProjectUnixNamesThatUseDocman()
    {
        $result = $this->dao->getActiveProjectUnixNamesThatUseDocman();

        $project_shortnames = [];
        while ($row = $result->getRow()) {
            $project_shortnames[] = $row['shortname'];
        }

        return $project_shortnames;
    }
}
