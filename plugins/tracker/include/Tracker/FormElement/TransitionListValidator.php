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

namespace Tuleap\Tracker\FormElement;

use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List_BindValue;
use TransitionFactory;

class TransitionListValidator
{
    /**
     * @var TransitionFactory
     */
    private $transition_factory;

    public function __construct(TransitionFactory $transition_factory)
    {
        $this->transition_factory = $transition_factory;
    }

    public function checkTransition(
        Field\ListField $field,
        $value,
        ?\Tracker_Artifact_Changeset $last_changeset = null,
    ) {
        if (! $last_changeset || $last_changeset->getValue($field) == null) {
            $from = null;
            $to   = $value;
        } else {
            $last_changeset_value = $last_changeset->getValue($field);
            \assert($last_changeset_value instanceof Tracker_Artifact_ChangesetValue_List);
            $list_values = $last_changeset_value->getListValues();
            $from        = reset($list_values);
            $to          = $this->extractValueSwitchFieldType($value);
        }
        $transition_id = $this->transition_factory->getTransitionId($field->getTracker(), $from, $to);

        if (! $field->userCanMakeTransition($transition_id)) {
            return false;
        }

        return true;
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    private function extractValueSwitchFieldType($value)
    {
        if (
            is_a($value, Tracker_Artifact_ChangesetValue_List::class) ||
            is_a($value, Tracker_FormElement_Field_List_BindValue::class)
        ) {
            return $value->getId();
        }

        return $value;
    }
}
