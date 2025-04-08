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

use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tracker_FormElementFactory;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueUnchanged;

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
            if (! $field) {
                continue;
            }
            if ($field instanceof Tracker_FormElement_Field_List) {
                if (is_array($data) && count($data) > 1) {
                    $key = array_search((string) BindStaticValueUnchanged::VALUE_ID, $data, true);
                    if ($key !== false) {
                        unset($data[$key]);
                    }
                }
            }
            if ($this->hasDataChanged($field, $data)) {
                $fields_data[$field_id] = $data;
            }
        }

        return $fields_data;
    }

    private function hasDataChanged(\Tracker_FormElement_Field $field, mixed $data): bool
    {
        if ($field instanceof Tracker_FormElement_Field_List) {
            return $this->isValueInData($data, (string) BindStaticValueUnchanged::VALUE_ID);
        } elseif ($field instanceof Tracker_FormElement_Field_PermissionsOnArtifact) {
            return isset($data[Tracker_FormElement_Field_PermissionsOnArtifact::DO_MASS_UPDATE_FLAG]) &&
                $data[Tracker_FormElement_Field_PermissionsOnArtifact::DO_MASS_UPDATE_FLAG] === '1';
        }

        return $this->isValueInData($data, $GLOBALS['Language']->getText('global', 'unchanged'));
    }

    private function isValueInData(mixed $data, string $value): bool
    {
        return (
            (is_array($data) && ! in_array($value, $data)) ||
            (! is_array($data) && $data !== $value)
        );
    }
}
