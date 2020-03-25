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

use Tuleap\Tracker\Workflow\PostAction\Update\SetIntValue;

class SetIntValueValidator
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
    public function validate(\Tracker $tracker, SetIntValue ...$set_int_values): void
    {
        try {
            $this->field_ids_validator->validate(...$set_int_values);
        } catch (DuplicateFieldIdException $e) {
            throw new InvalidPostActionException(
                dgettext(
                    'tuleap-tracker',
                    "There should not be duplicate field_ids for 'set_field_value' actions with type 'int'."
                )
            );
        }

        $int_field_ids = $this->extractIntFieldIds($tracker);
        foreach ($set_int_values as $set_int_value) {
            $this->validateSetIntValue($set_int_value, $int_field_ids);
        }
    }

    /**
     * @throws InvalidPostActionException
     */
    private function validateSetIntValue(SetIntValue $set_int_value, array $int_field_ids)
    {
        if (! in_array($set_int_value->getFieldId(), $int_field_ids, true)) {
            throw new InvalidPostActionException(
                sprintf(
                    dgettext(
                        'tuleap-tracker',
                        "The field_id value '%u' does not match an int field in use in the tracker."
                    ),
                    $set_int_value->getFieldId()
                )
            );
        }
    }

    private function extractIntFieldIds(\Tracker $tracker)
    {
        $int_fields    = $this->form_element_factory->getUsedIntFields($tracker);
        $int_field_ids = [];
        foreach ($int_fields as $int_field) {
            \assert($int_field instanceof \Tracker_FormElement_Field_Integer);
            $int_field_ids[] = (int) $int_field->getId();
        }
        return $int_field_ids;
    }
}
