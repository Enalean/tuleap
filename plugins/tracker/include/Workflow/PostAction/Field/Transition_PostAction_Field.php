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

use Tuleap\Tracker\FormElement\Field\TrackerField;

/**
 * Base class for field post actions.
 */

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
abstract class Transition_PostAction_Field extends Transition_PostAction
{
    /**
     * @var TrackerField The field the post action should modify
     */
    protected $field;

    /**
     * @param Transition                   $transition The transition the post action belongs to
     * @param int $id Id of the post action
     * @param TrackerField    $field      The field or field_id the post action should modify
     */
    public function __construct(Transition $transition, $id, $field)
    {
        parent::__construct($transition, $id);
        $this->field = $field;
    }

    /**
     * Return the field associated to this post action
     *
     * @return TrackerField
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
    #[\Override]
    public function bypassPermissions(TrackerField $field)
    {
        return $this->getFieldId() == $field->getId() && $this->bypass_permissions;
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
