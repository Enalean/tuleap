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

use LogicException;
use Tracker;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Workflow\PostAction\Update\HiddenFieldsetsValue;

class HiddenFieldsetsValueValidator
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
    public function validate(Tracker $tracker, HiddenFieldsetsValue ...$hidden_fieldsets_values): void
    {
        foreach ($hidden_fieldsets_values as $hidden_fieldsets) {
            if (count($hidden_fieldsets->getFieldsetIds()) !== count(array_unique($hidden_fieldsets->getFieldsetIds()))) {
                throw new InvalidPostActionException(
                    dgettext(
                        'tuleap-tracker',
                        "There should not be duplicate fieldset_ids for 'hidden_fieldsets' actions."
                    )
                );
            }

            $this->validateSelectedFieldset($tracker, $hidden_fieldsets);
        }
    }

    /**
     * @throws InvalidPostActionException
     */
    private function validateSelectedFieldset(Tracker $tracker, HiddenFieldsetsValue $hidden_fieldsets_value): void
    {
        $used_fieldset_ids = $this->extractUsedFieldsetIds($tracker);
        $workflow          = $tracker->getWorkflow();

        if ($workflow === null) {
            throw new LogicException('Tracker #' . $tracker->getId() . ' does not have a workflow');
        }

        foreach ($hidden_fieldsets_value->getFieldsetIds() as $fieldset_id) {
            $this->validateFieldsetIsUsedInTracker($fieldset_id, $used_fieldset_ids);
        }
    }

    private function extractUsedFieldsetIds(Tracker $tracker): array
    {
        $used_fieldsets    = $this->form_element_factory->getUsedFieldsets($tracker);
        $used_fieldset_ids = [];
        foreach ($used_fieldsets as $used_fieldset) {
            \assert($used_fieldset instanceof \Tracker_FormElement_Container_Fieldset);
            $used_fieldset_ids[] = (int) $used_fieldset->getID();
        }

        return $used_fieldset_ids;
    }

    /**
     * @throws InvalidPostActionException
     */
    private function validateFieldsetIsUsedInTracker(int $fieldset_id, array $used_fieldset_ids)
    {
        if (! in_array($fieldset_id, $used_fieldset_ids, true)) {
            throw new InvalidPostActionException(
                sprintf(
                    dgettext(
                        'tuleap-tracker',
                        "The fieldset_id value '%u' does not match a fieldset in use in the tracker."
                    ),
                    $fieldset_id
                )
            );
        }
    }
}
