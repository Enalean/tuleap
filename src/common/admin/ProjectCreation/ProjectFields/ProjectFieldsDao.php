<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\admin\ProjectCreation\ProjectFields;

use Tuleap\DB\DataAccessObject;

class ProjectFieldsDao extends DataAccessObject
{
    public function createProjectField(
        string $desc_name,
        string $desc_description,
        int $desc_rank,
        string $desc_type,
        bool $desc_required,
    ): void {
        $this->getDB()->insert(
            'group_desc',
            [
                'desc_name'        => $desc_name,
                'desc_description' => $desc_description,
                'desc_rank'        => $desc_rank,
                'desc_type'        => $desc_type,
                'desc_required'    => $desc_required,
            ]
        );
    }

    public function updateProjectField(
        int $group_desc_id,
        string $desc_name,
        string $desc_description,
        int $desc_rank,
        string $desc_type,
        bool $desc_required,
    ): void {
        $this->getDB()->update(
            'group_desc',
            [
                'desc_name'       => $desc_name,
                'desc_description' => $desc_description,
                'desc_rank'        => $desc_rank,
                'desc_type'        => $desc_type,
                'desc_required'    => $desc_required,
            ],
            ['group_desc_id' => $group_desc_id]
        );
    }

    public function deleteProjectField(int $group_desc_id): void
    {
        $this->getDB()->beginTransaction();
        $this->getDB()->delete('group_desc', ['group_desc_id' => $group_desc_id]);
        $this->getDB()->delete('group_desc_value', ['group_desc_id' => $group_desc_id]);
        $this->getDB()->commit();
    }
}
