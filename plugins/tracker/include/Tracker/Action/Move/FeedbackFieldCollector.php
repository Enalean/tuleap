<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action\Move;

use Tracker;
use Tracker_FormElement_Field;

final class FeedbackFieldCollector implements FeedbackFieldCollectorInterface
{
    /**
     * @var Tracker_FormElement_Field[]
     */
    private $fields_fully_migrated = [];

    /**
     * @var Tracker_FormElement_Field[]
     */
    private $fields_not_migrated = [];

    /**
     * @var Tracker_FormElement_Field[]
     */
    private $fields_partially_migrated = [];

    public function initAllTrackerFieldAsNotMigrated(Tracker $tracker): void
    {
        foreach ($tracker->getFormElementFields() as $field) {
            if (! $field->isUpdateable()) {
                continue;
            }

            $this->addFieldInNotMigrated($field);
        }
    }

    public function addFieldInNotMigrated(Tracker_FormElement_Field $field): void
    {
        $field_id = $field->getId();

        $this->removeField($field_id);

        $this->fields_not_migrated[$field_id] = $field;
    }

    public function addFieldInFullyMigrated(Tracker_FormElement_Field $field): void
    {
        $field_id = $field->getId();

        $this->removeField($field_id);

        $this->fields_fully_migrated[$field_id] = $field;
    }

    public function addFieldInPartiallyMigrated(Tracker_FormElement_Field $field): void
    {
        $field_id = $field->getId();

        $this->removeField($field_id);

        $this->fields_partially_migrated[$field_id] = $field;
    }

    private function removeField($field_id)
    {
        if (isset($this->fields_fully_migrated[$field_id])) {
            unset($this->fields_fully_migrated[$field_id]);
        }

        if (isset($this->fields_not_migrated[$field_id])) {
            unset($this->fields_not_migrated[$field_id]);
        }

        if (isset($this->fields_partially_migrated[$field_id])) {
            unset($this->fields_partially_migrated[$field_id]);
        }
    }

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getFieldsFullyMigrated(): array
    {
        return $this->fields_fully_migrated;
    }

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getFieldsNotMigrated(): array
    {
        return $this->fields_not_migrated;
    }

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getFieldsPartiallyMigrated(): array
    {
        return $this->fields_partially_migrated;
    }
}
