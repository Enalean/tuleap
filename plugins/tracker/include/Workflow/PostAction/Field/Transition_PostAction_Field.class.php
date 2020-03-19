<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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
 * Base class for field post actions.
 */

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
abstract class Transition_PostAction_Field extends Transition_PostAction
{

    /**
     * @var Tracker_FormElement_Field The field the post action should modify
     */
    protected $field;

    /**
     * Constructor
     *
     * @param Transition                   $transition The transition the post action belongs to
     * @param int $id Id of the post action
     * @param Tracker_FormElement_Field    $field      The field the post action should modify
     */
    public function __construct(Transition $transition, $id, $field)
    {
        parent::__construct($transition, $id);
        $this->field = $field;
    }

    /**
     * Return the field associated to this post action
     *
     * @return Tracker_FormElement_Field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Return ID of the field updated by the post-action
     *
     * @return int
     */
    public function getFieldId()
    {
        if ($this->field) {
            return $this->field->getId();
        } else {
            return 0;
        }
    }

    /**
     * Get the value of bypass_permissions
     *
     *
     * @return bool
     */
    public function bypassPermissions(Tracker_FormElement_Field $field)
    {
        return $this->getFieldId() == $field->getId() && $this->bypass_permissions;
    }

    /**
     * Check if a post action on field_id already exists
     *
     * @param mixed $request_field_id the field_id from the request
     *
     * @return int a field id
     */
    public function getFieldIdOfPostActionToUpdate($request_field_id)
    {
        $field_id = $this->getFieldId();

        if ($request_field_id != $field_id) {
            $new_field = $this->getFormElementFactory()->getUsedFormElementById($request_field_id);

            if ($new_field) {
                $already_used = $this->getDao()->searchByTransitionIdAndFieldId($this->transition->getId(), $new_field->getId());

                if (count($already_used)) {
                    $this->addFeedback(
                        'error',
                        $GLOBALS['Language']->getText(
                            'workflow_admin',
                            'postaction_on_field_already_exist',
                            [$new_field->getLabel()]
                        )
                    );
                } else {
                    $field_id = $new_field->getId();
                    return $field_id;
                }
            }
        }
        return $field_id;
    }

    /**
     * Wrapper for Tracker_FormElementFactory
     *
     * @return Tracker_FormElementFactory
     */
    protected function getFormElementFactory()
    {
        return Tracker_FormElementFactory::instance();
    }
}
