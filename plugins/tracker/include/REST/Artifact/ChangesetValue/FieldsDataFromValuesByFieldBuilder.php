<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact\ChangesetValue;

final class FieldsDataFromValuesByFieldBuilder
{
    public function __construct(private \Tracker_FormElementFactory $formelement_factory)
    {
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldException
     * @throws \Tracker_FormElement_InvalidFieldValueException
     */
    public function getFieldsDataOnCreate(array $values, \Tracker $tracker): array
    {
        $new_values = [];
        foreach ($values as $field_name => $value) {
            $field = $this->getFieldByName($tracker, $field_name);

            $new_values[$field->getId()] = $field->getFieldDataFromRESTValueByfield($value);
        }

        return $new_values;
    }

    /**
     * @throws \Tracker_FormElement_InvalidFieldException
     */
    private function getFieldByName(\Tracker $tracker, string $field_name): \Tracker_FormElement_Field
    {
        $field = $this->formelement_factory->getUsedFieldByName($tracker->getId(), $field_name);
        if (! $field) {
            throw new \Tracker_FormElement_InvalidFieldException("Field $field_name does not exist in the tracker");
        }

        return $field;
    }
}
