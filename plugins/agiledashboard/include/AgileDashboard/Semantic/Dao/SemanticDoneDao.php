<?php
/**
 * Copyright Enalean (c) 2017-2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\AgileDashboard\Semantic\Dao;

use DataAccessException;
use DataAccessObject;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;

class SemanticDoneDao extends DataAccessObject
{
    public function __construct(?LegacyDataAccessInterface $da = null)
    {
        parent::__construct($da);
        $this->enableExceptionsOnError();
    }

    public function getSelectedValues($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT value_id
                FROM plugin_agiledashboard_semantic_done
                WHERE tracker_id = $tracker_id";

        return $this->retrieve($sql);
    }

    public function isValueADoneValue($tracker_id, $value_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $value_id   = $this->da->escapeInt($value_id);

        $sql = "SELECT NULL
                FROM plugin_agiledashboard_semantic_done
                WHERE tracker_id = $tracker_id
                  AND value_id = $value_id";

        return count($this->retrieve($sql)) > 0;
    }

    public function getSemanticStatement($field_id, $tracker_id)
    {
        $field_id   = $this->da->escapeInt($field_id);
        $tracker_id = $this->da->escapeInt($tracker_id);

        return "SELECT IF(static_value.original_value_id, static_value.original_value_id, static_value.id) AS id
                FROM tracker_field_list_bind_static_value AS static_value
                    INNER JOIN plugin_agiledashboard_semantic_done AS semantic_done
                    ON (semantic_done.value_id = static_value.id OR semantic_done.value_id = static_value.original_value_id)
                WHERE semantic_done.tracker_id = $tracker_id
                    AND static_value.field_id = $field_id";
    }

    public function clearForTracker($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "DELETE FROM plugin_agiledashboard_semantic_done
                WHERE tracker_id = $tracker_id";

        return $this->update($sql);
    }

    public function addForTracker($tracker_id, array $selected_values)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $values = [];
        foreach ($selected_values as $value_id) {
            $value_id = $this->da->escapeInt($value_id);
            $values[] = "($tracker_id, $value_id)";
        }

        $values_statement = implode(', ', $values);

        $sql = "INSERT INTO plugin_agiledashboard_semantic_done (tracker_id, value_id)
                VALUES $values_statement";

        return $this->update($sql);
    }

    public function updateForTracker($tracker_id, array $selected_values)
    {
        $this->startTransaction();
        try {
            $this->clearForTracker($tracker_id);
            $this->addForTracker($tracker_id, $selected_values);
            $this->commit();
        } catch (DataAccessException $e) {
            $this->rollBack();
            throw $e;
        }
    }
}
