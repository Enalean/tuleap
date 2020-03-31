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

use Tracker;
use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;

class SetDateValueValidator
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
    public function validate(Tracker $tracker, SetDateValue ...$set_date_values): void
    {
        try {
            $this->field_ids_validator->validate(...$set_date_values);
        } catch (DuplicateFieldIdException $e) {
            throw new InvalidPostActionException(
                dgettext(
                    'tuleap-tracker',
                    "There should not be duplicate field_ids for 'set_field_value' actions with type 'date'."
                )
            );
        }

        $date_field_ids = $this->extractDateFieldIds($tracker);
        foreach ($set_date_values as $set_date_value) {
            $this->validateSetDateValue($set_date_value, $date_field_ids);
        }
    }


    /**
     * @throws InvalidPostActionException
     */
    private function validateSetDateValue(SetDateValue $set_date_value, array $date_field_ids)
    {
        if (! in_array($set_date_value->getFieldId(), $date_field_ids, true)) {
            throw new InvalidPostActionException(
                sprintf(
                    dgettext(
                        'tuleap-tracker',
                        "The field_id value '%u' does not match a date field in use in the tracker."
                    ),
                    $set_date_value->getFieldId()
                )
            );
        }
    }

    private function extractDateFieldIds(Tracker $tracker): array
    {
        $date_fields    = $this->form_element_factory->getUsedCustomDateFields($tracker);
        $date_field_ids = [];
        foreach ($date_fields as $date_field) {
            \assert($date_field instanceof \Tracker_FormElement_Field_Date);
            $date_field_ids[] = (int) $date_field->getId();
        }
        return $date_field_ids;
    }
}
