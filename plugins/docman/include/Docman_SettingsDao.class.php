<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class Docman_SettingsDao extends DataAccessObject
{

    public function searchByGroupId($group_id)
    {
        $sql = sprintf('SELECT * FROM plugin_docman_project_settings WHERE group_id = %d', $group_id);
        return $this->retrieve($sql);
    }

    public function searchViewByGroupId($group_id)
    {
        $sql = 'SELECT view FROM plugin_docman_project_settings WHERE group_id = ' . $this->da->quoteSmart($group_id);
        return $this->retrieve($sql);
    }

    public function create($group_id, $view, $use_obsolescence_date = 0, $use_status = 0)
    {
        $sql = sprintf(
            'INSERT INTO plugin_docman_project_settings(' .
                       'group_id, view, use_obsolescence_date, use_status' .
                       ') VALUES (' .
                       '%d, %s, %d, %d' .
                       ')',
            $group_id,
            $this->da->quoteSmart($view),
            $use_obsolescence_date,
            $use_status
        );
         return $this->update($sql);
    }

    public function updateViewForGroupId($group_id, $view)
    {
        $sql = 'UPDATE plugin_docman_project_settings SET view = ' . $this->da->quoteSmart($view) . ' WHERE group_id = ' . $this->da->quoteSmart($group_id);
        return $this->update($sql);
    }

    public function updateMetadataUsageForGroupId($group_id, $label, $useIt)
    {
        $sql = sprintf(
            'UPDATE plugin_docman_project_settings' .
                       ' SET use_%s = %d' .
                       ' WHERE group_id = %d',
            $label,
            $useIt,
            $group_id
        );
        return $this->update($sql);
    }
}
