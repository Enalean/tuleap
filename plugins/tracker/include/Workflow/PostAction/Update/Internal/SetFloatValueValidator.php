<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Tuleap\Tracker\Workflow\PostAction\Update\SetFloatValue;

class SetFloatValueValidator
{
    /**
     * @var PostActionFieldIdValidator
     */
    private $field_ids_validator;
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(
        PostActionFieldIdValidator $field_ids_validator,
        \Tracker_FormElementFactory $form_element_factory
    ) {
        $this->field_ids_validator  = $field_ids_validator;
        $this->form_element_factory = $form_element_factory;
    }

    /**
     * @throws InvalidPostActionException
     */
    public function validate(\Tracker $tracker, SetFloatValue ...$set_float_values): void
    {
        try {
            $this->field_ids_validator->validate(...$set_float_values);
        } catch (DuplicateFieldIdException $e) {
            throw new InvalidPostActionException(
                dgettext(
                    'tuleap-tracker',
                    "There should not be duplicate field_ids for 'set_field_value' actions with type 'float'."
                )
            );
        }

        $float_field_ids = $this->extractFloatFieldIds($tracker);
        foreach ($set_float_values as $set_float_value) {
            $this->validateSetFloatValue($set_float_value, $float_field_ids);
        }
    }

    /**
     * @throws InvalidPostActionException
     */
    private function validateSetFloatValue(SetFloatValue $set_float_value, array $float_field_ids)
    {
        if (! in_array($set_float_value->getFieldId(), $float_field_ids, true)) {
            throw new InvalidPostActionException(
                sprintf(
                    dgettext(
                        'tuleap-tracker',
                        "The field_id value '%u' does not match a float field in use in the tracker."
                    ),
                    $set_float_value->getFieldId()
                )
            );
        }
    }

    private function extractFloatFieldIds(\Tracker $tracker) : array
    {
        $float_fields    = $this->form_element_factory->getUsedFormElementsByType($tracker, 'float');
        $float_field_ids = [];
        foreach ($float_fields as $float_field) {
            \assert($float_field instanceof \Tracker_FormElement_Field_Float);
            $float_field_ids[] = (int) $float_field->getId();
        }

        return $float_field_ids;
    }
}
