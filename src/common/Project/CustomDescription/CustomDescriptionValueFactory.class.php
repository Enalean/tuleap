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
 * Factory to instanciate Project_CustomDescription_CustomDescription value
 */
class Project_CustomDescription_CustomDescriptionValueFactory
{

    /** @var Project_CustomDescription_CustomDescriptionValueDao */
    private $dao;

    public function __construct(Project_CustomDescription_CustomDescriptionValueDao $dao)
    {
        $this->dao = $dao;
    }

    public function getDescriptionFieldsValue(Project $project)
    {
        $project_id                = $project->getID();
        $description_fields_values = array();
        $results                   = $this->dao->getDescriptionFieldsValue($project_id);

        while ($row = $results->getRow()) {
            $description_fields_values[] = array(
                'id'    => $row['group_desc_id'],
                'value' => $row['value']
            );
        }
        return $description_fields_values;
    }
}
