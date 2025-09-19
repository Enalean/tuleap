<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\Shareable;

use DataAccessObject;
use DataAccessQueryException;
use Tuleap\Tracker\FormElement\TrackerFormElement;

class PropagatePropertiesDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->enableExceptionsOnError();
    }

    public function propagateProperties(TrackerFormElement $original_field)
    {
        $this->startTransaction();
        try {
            $this->propagateNameIfPossible($original_field);
            $this->propagateLabelAndDescription($original_field);

            $this->commit();
            return true;
        } catch (DataAccessQueryException $exception) {
            $this->rollBack();
            return false;
        }
    }

    private function propagateNameIfPossible(TrackerFormElement $original_field)
    {
        $original_field_id = $this->da->escapeInt($original_field->id);

        $sql = "UPDATE tracker_field AS original
                  INNER JOIN tracker_field AS target_to_rename ON (
                    target_to_rename.original_field_id = original.id
                    AND original.id = $original_field_id
                  )
                  LEFT JOIN (
                    SELECT target.tracker_id
                    FROM tracker_field AS original
                      INNER JOIN tracker_field AS target ON (
                        target.original_field_id = original.id
                        AND original.id = $original_field_id
                      )
                      INNER JOIN tracker_field ON (
                        tracker_field.tracker_id = target.tracker_id
                        AND tracker_field.name = original.name
                      )
                  ) AS target_not_updatable ON (
                    target_to_rename.tracker_id = target_not_updatable.tracker_id
                  )
                SET target_to_rename.name = original.name
                WHERE target_not_updatable.tracker_id IS NULL";

        $this->update($sql);
    }

    private function propagateLabelAndDescription(TrackerFormElement $original_field)
    {
        $original_field_id = $this->da->escapeInt($original_field->id);

        $sql = "UPDATE tracker_field AS original
                    INNER JOIN tracker_field AS target ON (target.original_field_id = original.id)
                    SET target.label = original.label,
                        target.description = original.description
                    WHERE original.id = $original_field_id";

        $this->update($sql);
    }
}
