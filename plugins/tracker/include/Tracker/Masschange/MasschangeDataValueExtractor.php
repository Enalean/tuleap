<?php
/*
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Masschange;

use Tracker_FormElementFactory;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueUnchanged;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField;

class MasschangeDataValueExtractor
{
    public function __construct(private readonly Tracker_FormElementFactory $form_element_factory)
    {
    }

    public function getNewValues(array $masschange_data): array
    {
        $fields_data = [];
        foreach ($masschange_data as $field_id => $data) {
            $field = $this->form_element_factory->getFieldById($field_id);
            if ($field === null) {
                continue;
            }

            if ($field instanceof ListField) {
                $data = $this->removeUnchangedValueForMultiSelectWhenMoreThanAValueIsSubmitted($data);
            }

            if ($this->hasDataChanged($field, $data)) {
                $fields_data[$field_id] = $data;
            }
        }

        return $fields_data;
    }

    private function removeUnchangedValueForMultiSelectWhenMoreThanAValueIsSubmitted(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        if (count($data) === 1) {
            return $data;
        }

        $key = array_search((string) BindStaticValueUnchanged::VALUE_ID, $data, true);
        if ($key !== false) {
            unset($data[$key]);
        }

        return $data;
    }

    private function hasDataChanged(\Tuleap\Tracker\FormElement\Field\TrackerField $field, mixed $data): bool
    {
        if ($field instanceof ListField) {
            return $this->hasListValueChanged($data);
        }

        if ($field instanceof PermissionsOnArtifactField) {
            return $this->hasPermissionsOnArtifactChanged($data);
        }

        return $this->isValueNotInData($data, $GLOBALS['Language']->getText('global', 'unchanged'));
    }

    private function hasListValueChanged(mixed $data): bool
    {
        return $this->isValueNotInData($data, (string) BindStaticValueUnchanged::VALUE_ID);
    }

    private function hasPermissionsOnArtifactChanged(mixed $data): bool
    {
        return isset($data[PermissionsOnArtifactField::DO_MASS_UPDATE_FLAG]) &&
               $data[PermissionsOnArtifactField::DO_MASS_UPDATE_FLAG] === '1';
    }

    private function isValueNotInData(mixed $data, string $value): bool
    {
        if (is_array($data)) {
            return ! in_array($value, $data);
        }

        return $data !== $value;
    }
}
