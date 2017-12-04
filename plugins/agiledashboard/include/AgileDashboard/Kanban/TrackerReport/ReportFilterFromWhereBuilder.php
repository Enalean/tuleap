<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Kanban\TrackerReport;

use CodendiDataAccess;
use Tracker;
use Tuleap\AgileDashboard\Kanban\ColumnIdentifier;
use Tuleap\Tracker\Report\Query\FromWhere;

class ReportFilterFromWhereBuilder
{
    public function getFromWhere(Tracker $kanban_tracker, ColumnIdentifier $column_identifier)
    {
        $tracker_id                  = $this->escapeInt($kanban_tracker->getId());
        $changeset_value_alias       = 'CV2';
        $changeset_value_field_alias = 'CVL';

        $from = " INNER JOIN (
                tracker_changeset_value as $changeset_value_alias
                INNER JOIN (
                    SELECT distinct(field_id) FROM tracker_semantic_status WHERE tracker_id = $tracker_id
                ) AS SS
                    ON ($changeset_value_alias.field_id = SS.field_id)
                INNER JOIN tracker_changeset_value_list AS $changeset_value_field_alias
                    ON ($changeset_value_alias.id = $changeset_value_field_alias.changeset_value_id)
            ) ON ($changeset_value_alias.changeset_id = c.id)";

        if ($column_identifier->isBacklog()) {
            $where = "($changeset_value_field_alias.bindvalue_id IS NULL
                OR $changeset_value_field_alias.bindvalue_id = 100)";
        } elseif ($column_identifier->isArchive()) {
            $from .= " LEFT JOIN tracker_semantic_status SS2
                ON (SS2.field_id = CV2.field_id AND SS2.open_value_id = CVL.bindvalue_id)";

            $where = "SS2.open_value_id IS NULL
                  AND $changeset_value_field_alias.bindvalue_id IS NOT NULL
                  AND $changeset_value_field_alias.bindvalue_id <> 100";
        } else {
            $column_id = $this->escapeInt($column_identifier->getColumnId());
            $where     = "$changeset_value_field_alias.bindvalue_id = $column_id";
        }

        return new FromWhere($from, $where);
    }

    public function escapeInt($value)
    {
        return CodendiDataAccess::instance()->escapeInt($value);
    }
}
