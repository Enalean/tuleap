<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


/**
 * Base class for numeric field post actions.
 */
abstract class Transition_PostAction_Field_Numeric extends Transition_PostAction_Field
{//phpcs:ignore

    /**
     * @var int|float the value
     */
    protected $value;

    /**
     * Constructor
     *
     * @param Transition                   $transition The transition the post action belongs to
     * @param int $id Id of the post action
     * @param Tracker_FormElement_Field    $field      The field the post action should modify
     * @param int|float $value The value to set
     */
    public function __construct(Transition $transition, $id, $field, $value)
    {
        parent::__construct($transition, $id, $field);
        $this->value = $value;
    }

    /**
     * Say if the action is well defined
     *
     * @return bool
     */
    public function isDefined()
    {
        return $this->getField() && ($this->value !== null);
    }

    /**
     * @return int|float The value set on the field by the post action.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Execute actions before transition happens
     *
     * @param Array &$fields_data Request field data (array[field_id] => data)
     * @param PFUser  $current_user The user who are performing the update
     *
     * @return void
     */
    public function before(array &$fields_data, PFUser $current_user)
    {
        // Do something only if the value and the float field are properly defined
        if ($this->isDefined()) {
            $field = $this->getField();
            if ($field->userCanRead($current_user)) {
                $this->addFeedback(
                    'info',
                    $GLOBALS['Language']->getText(
                        'workflow_postaction',
                        'field_value_set',
                        [$field->getLabel(), $this->value]
                    )
                );
            }

            $fields_data[$this->field->getId()] = $this->value;
            $this->bypass_permissions = true;
        }
    }
}
