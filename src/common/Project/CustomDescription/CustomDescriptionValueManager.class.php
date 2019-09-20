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

require_once 'CustomDescriptionValueDao.class.php';

/**
 * Manager for Project_CustomDescription_CustomDescription
 */
class Project_CustomDescription_CustomDescriptionValueManager
{

    /** @var Project_CustomDescription_CustomDescriptionValueDao */
    private $dao;

    public function __construct(Project_CustomDescription_CustomDescriptionValueDao $dao)
    {
        $this->dao = $dao;
    }

    public function setCustomDescription(Project $project, $field_id_to_update, $field_value)
    {
        $group_id = $project->getID();
        $this->dao->setDescriptionFieldValue($group_id, $field_id_to_update, $field_value);
    }
}
