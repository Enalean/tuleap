<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Widget\Note;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\DB\DataAccessObject;

class NoteDao extends DataAccessObject
{

    public function get($id)
    {
        return $this->getDB()->row('SELECT title, content FROM widget_note WHERE id = ?', $id);
    }

    /**
     * @param $project_id
     * @return string
     * @throws \Exception
     */
    public function create($project_id, $title, $content)
    {
        return $this->getDB()->insertReturnId(
            'widget_note',
            [
                'owner_id'   => $project_id,
                'owner_type' => ProjectDashboardController::LEGACY_DASHBOARD_TYPE,
                'title'      => $title,
                'content'    => $content,
            ]
        );
    }

    public function update($id, $title, $content)
    {
        return $this->getDB()->update(
            'widget_note',
            [
                'title'   => $title,
                'content' => $content,
            ],
            EasyStatement::open()->with('id = ?', $id)
        );
    }

    public function delete($id)
    {
        return $this->getDB()->delete('widget_note', EasyStatement::open()->with('id = ?', $id));
    }

    public function duplicate($new_project_id, $id)
    {
        $sql = 'INSERT INTO widget_note (owner_id, owner_type, title, content)
                SELECT ?, ?, title, content
                FROM widget_note
                WHERE id = ?';
        $this->getDB()->safeQuery($sql, [$new_project_id, ProjectDashboardController::LEGACY_DASHBOARD_TYPE, $id]);
        return $this->getDB()->lastInsertId();
    }
}
