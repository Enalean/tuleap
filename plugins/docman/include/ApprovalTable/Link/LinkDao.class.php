<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

class Docman_ApprovalTableLinkDao extends Docman_ApprovalTableItemDao
{

    public function getTableById($versionId, $fields = '*')
    {
        $sql = 'SELECT ' . $fields .
            ' FROM plugin_docman_approval' .
            ' WHERE link_version_id = ' . $this->da->escapeInt($versionId);
        return $this->retrieve($sql);
    }

    public function getTableByItemId($itemId, $fields = '*')
    {
        return $this->getLatestTableByItemId($itemId, $fields);
    }

    public function getLatestTableByItemId($itemId, $fields = 'app.*')
    {
        return $this->getApprovalTableItemId($itemId, $fields, ' LIMIT 1', true);
    }

    public function getApprovalTableItemId($itemId, $fields = 'app.*', $limit = '', $tableStatus = false)
    {
        $fields .= ', ver.number as version_number';
        $where = ' ver.item_id = ' . $this->da->escapeInt($itemId) .
            ' AND app.wiki_version_id IS NULL';
        $join = ' JOIN plugin_docman_link_version ver ON (ver.id = app.link_version_id)';
        $orderBy = ' ORDER BY ver.number DESC ';

        return $this->getTableWithStatus($tableStatus, $fields, $where, $join, $orderBy, $limit);
    }
}
