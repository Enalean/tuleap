<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

class Project_CustomDescription_CustomDescriptionDao extends DataAccessObject //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{

    /**
     *
     * @return DataAccessResult
     */
    public function getCustomDescriptions()
    {
        $sql = 'SELECT *
                FROM group_desc
                ORDER BY desc_rank';

        return $this->retrieve($sql);
    }

    /**
     * @param int $id
     *
     * @return DataAccessResult
     */
    public function getCustomDescription($id)
    {
        $id = $this->da->escapeInt($id);

        $sql = "SELECT *
                FROM group_desc
                WHERE group_desc_id = $id";

        return $this->retrieve($sql);
    }

    /**
     *
     * @return DataAccessResult
     */
    public function getRequiredCustomDescriptions()
    {
        $sql = 'SELECT *
                FROM group_desc
                WHERE desc_required = 1
                ORDER BY desc_rank';

        return $this->retrieve($sql);
    }

    /**
     * @throws DataAccessQueryException
     */
    public function updateRequiredCustomDescription(bool $required, int $id): void
    {
        $required = $this->da->escapeInt($required);
        $id = $this->da->escapeInt($id);

        $sql    = "UPDATE group_desc SET desc_required=$required where group_desc_id=$id";
        $this->update($sql);
    }
}
