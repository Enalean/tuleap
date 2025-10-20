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

namespace Tuleap\Cardwall\Semantic;

use Tracker_FormElementFactory;
use Tuleap\Tracker\FormElement\Field\List\Bind\Static\ListFieldStaticBind;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Tracker;

class BackgroundColorPresenterBuilder
{
    private Tracker_FormElementFactory $form_element_factory;
    private BackgroundColorDao $dao;

    public function __construct(Tracker_FormElementFactory $form_element_factory, BackgroundColorDao $dao)
    {
        $this->form_element_factory = $form_element_factory;
        $this->dao                  = $dao;
    }

    public function build(array $form_elements_fields, Tracker $tracker): BackgroundColorSelectorPresenter
    {
        $selected_field_id  = $this->dao->searchBackgroundColor($tracker->getId());
        $has_selected_field = false;
        $url                = '';
        if ($selected_field_id !== false) {
            $has_selected_field = true;
            $selected_field     = $this->form_element_factory->getFieldById($selected_field_id);
            if ($selected_field) {
                $url = $selected_field->getAdminEditUrl();
            }
        }

        return new BackgroundColorSelectorPresenter(
            $this->getTrackerFields($form_elements_fields, $selected_field_id),
            $has_selected_field,
            $url
        );
    }

    /**
     * @param TrackerField[] $form_elements_fields
     * @param                             $selected_field_id
     *
     * @return array
     */
    private function getTrackerFields(array $form_elements_fields, $selected_field_id)
    {
        $formatted_field = [];

        foreach ($form_elements_fields as $field) {
            if ($this->isFieldAListBoundToStaticValues($field)) {
                $formatted_field[] = [
                    'id'          => $field->getId(),
                    'name'        => $field->getLabel(),
                    'is_selected' => (int) $field->getId() === (int) $selected_field_id,
                ];
            }
        }

        return $formatted_field;
    }

    private function isFieldAListBoundToStaticValues(TrackerField $field): bool
    {
        return ($this->form_element_factory->getType($field) === 'sb'
                || $this->form_element_factory->getType($field) === 'rb')
            && $field->getBind()->getType() === ListFieldStaticBind::TYPE
            && $this->doesNotHaveAValueBoundToOldColorPicker($field);
    }

    private function doesNotHaveAValueBoundToOldColorPicker(TrackerField $field): bool
    {
        /**
         * @var \Tracker_FormElement_Field_List_BindDecorator[] $decorators
         */
        $decorators = $field->getBind()->getDecorators();

        if (count($decorators) === 0) {
            return true;
        }

        foreach ($decorators as $decorator) {
            if ($decorator->isUsingOldPalette()) {
                return false;
            }
        }

        return true;
    }
}
