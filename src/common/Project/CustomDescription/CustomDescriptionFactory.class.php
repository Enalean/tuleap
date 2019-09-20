<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'CustomDescription.class.php';
require_once 'CustomDescriptionDao.class.php';

/**
 * Factory to instanciate Project_CustomDescription_CustomDescription
 */
class Project_CustomDescription_CustomDescriptionFactory
{

    /** @var Project_CustomDescription_CustomDescriptionDao */
    private $dao;

    public function __construct(Project_CustomDescription_CustomDescriptionDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return Project_CustomDescription_CustomDescription[]
     */
    public function getRequiredCustomDescriptions()
    {
        $required_custom_descriptions = array();
        $res = $this->dao->getRequiredCustomDescriptions();
        while ($row = $res->getRow()) {
            $required_custom_descriptions[$row['group_desc_id']] = $this->getInstanceFromRow($row);
        }
        return $required_custom_descriptions;
    }

    public function getCustomDescription($id)
    {
        $res = $this->dao->getCustomDescription($id);

        if ($res && $res->rowCount() == 1) {
            $row = $res->getRow();
            return $this->getInstanceFromRow($row);
        }
        return null;
    }

    /**
     * @return Project_CustomDescription_CustomDescription[]
     */
    public function getCustomDescriptions()
    {
        $custom_descriptions = array();
        $res = $this->dao->getCustomDescriptions();
        while ($row = $res->getRow()) {
            $custom_descriptions[$row['group_desc_id']] = $this->getInstanceFromRow($row);
        }
        return $custom_descriptions;
    }

    /**
     * Buil an instance of CustomDescription
     *
     * @param array $row the value of the CustomDescription form the db
     *
     * @return CustomDescription
     */
    public function getInstanceFromRow(array $row)
    {
        return new Project_CustomDescription_CustomDescription(
            $row['group_desc_id'],
            $row['desc_name'],
            $row['desc_description'],
            $row['desc_required'],
            $row['desc_type'],
            $row['desc_rank']
        );
    }
}
