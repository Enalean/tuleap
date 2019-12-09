<?php
/**
 * Copyright (c) Enalean, 2016 - present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Project;

class DescriptionFieldsFactory
{

    /**
     * @var DescriptionFieldsDao
     */
    private $dao;

    public function __construct(DescriptionFieldsDao $dao)
    {
        $this->dao = $dao;
    }

    public function getAllDescriptionFields(): array
    {
        $description_fields = array();
        foreach ($this->dao->searchAll() as $row) {
            $description_fields[] = $row;
        }

        return $description_fields;
    }
}
