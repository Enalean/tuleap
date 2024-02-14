<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueUnchanged;

class Tracker_MasschangeDataValueExtractor
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    public function getNewValues(array $masschange_data)
    {
        $fields_data = [];
        foreach ($masschange_data as $field_id => $data) {
            if ($this->hasDataChanged($field_id, $data)) {
                $fields_data[$field_id] = $data;
            }
        }

        return $fields_data;
    }

    private function hasDataChanged(int $field_id, $data): bool
    {
        $field = $this->form_element_factory->getFieldById($field_id);
        if ($field instanceof Tracker_FormElement_Field_List) {
            return $this->isValueInData($data, (string) BindStaticValueUnchanged::VALUE_ID);
        } elseif ($field instanceof Tracker_FormElement_Field_PermissionsOnArtifact) {
            return isset($data[Tracker_FormElement_Field_PermissionsOnArtifact::DO_MASS_UPDATE_FLAG]) &&
                $data[Tracker_FormElement_Field_PermissionsOnArtifact::DO_MASS_UPDATE_FLAG] === '1';
        }

        return $this->isValueInData($data, $GLOBALS['Language']->getText('global', 'unchanged'));
    }

    private function isValueInData($data, string $value): bool
    {
        return (
            (is_array($data) && ! in_array($value, $data)) ||
            (! is_array($data) && $data !== $value)
        );
    }
}
