<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
use Tracker_FormElementFactory;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFields;

class FrozenFieldsValidator
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    /**
     * @throws InvalidPostActionException
     */
    public function validate(Tracker $tracker, FrozenFields ...$frozen_fields): void
    {
        $used_field_ids = $this->extractUsedFieldIds($tracker);
        foreach ($frozen_fields as $frozen_field) {
            if (count($frozen_field->getFieldIds()) !== count(array_unique($frozen_field->getFieldIds()))) {
                throw new InvalidPostActionException(
                    dgettext(
                        'tuleap-tracker',
                        "There should not be duplicate field_ids for 'frozen_fields' actions."
                    )
                );
            }

            $this->validateSelectedField($tracker, $frozen_field, $used_field_ids);
        }
    }

    private function extractUsedFieldIds(Tracker $tracker) : array
    {
        $used_fields    = $this->form_element_factory->getUsedFields($tracker);
        $used_field_ids = [];
        /** @var \Tracker_FormElement_Field $used_field */
        foreach ($used_fields as $used_field) {
            $used_field_ids[] = (int) $used_field->getId();
        }

        return $used_field_ids;
    }

    /**
     * @throws InvalidPostActionException
     */
    private function validateSelectedField(Tracker $tracker, FrozenFields $frozen_fields, array $used_field_ids) : void
    {
        $workflow_field_id = (int) $tracker->getWorkflow()->getFieldId();

        foreach ($frozen_fields->getFieldIds() as $field_id) {
            if (! in_array($field_id, $used_field_ids, true)) {
                throw new InvalidPostActionException(
                    sprintf(
                        dgettext(
                            'tuleap-tracker',
                            "The field_id value '%u' does not match a field in use in the tracker."
                        ),
                        $field_id
                    )
                );
            }

            if ($field_id === $workflow_field_id) {
                throw new InvalidPostActionException(
                    sprintf(
                        dgettext(
                            'tuleap-tracker',
                            "The field '%u' is used for the workflow cannot be defined in frozen fields actions."
                        ),
                        $field_id
                    )
                );
            }
        }
    }
}
