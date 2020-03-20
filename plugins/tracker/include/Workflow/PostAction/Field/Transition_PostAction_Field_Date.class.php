<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

use Tuleap\Tracker\Workflow\PostAction\Visitor;

/**
 * Set the date of a field
 */

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Transition_PostAction_Field_Date extends Transition_PostAction_Field
{

    /**
     * @const Clear the date.
     */
    public const CLEAR_DATE = 1;

    /**
     * @const Fill the date to the current time
     */
    public const FILL_CURRENT_TIME = 2;

    public const SHORT_NAME   = 'field_date';
    public const XML_TAG_NAME = 'postaction_field_date';

    /**
     * @var int the type of the value. CLEAR_DATE | FILL_CURRENT_TIME
     */
    protected $value_type;

    /**
     * Constructor
     *
     * @param Transition                   $transition The transition the post action belongs to
     * @param int $id Id of the post action
     * @param Tracker_FormElement_Field    $field      The field the post action should modify
     * @param int $value_type The type of the value to set
     */
    public function __construct(Transition $transition, $id, $field, $value_type)
    {
        parent::__construct($transition, $id, $field);
        $this->value_type = $value_type;
    }

    /**
     * Get the shortname of the post action
     *
     * @return string
     */
    public function getShortName()
    {
        return self::SHORT_NAME;
    }

    /**
     * Get the value type of the post action
     *
     * @return int
     */
    public function getValueType()
    {
        return $this->value_type;
    }

    /**
     * Get the label of the post action
     *
     * @return string
     */
    public static function getLabel()
    {
        return $GLOBALS['Language']->getText('workflow_admin', 'post_action_change_value_date_field');
    }

    /**
     * Say if the action is well defined
     *
     * @return bool
     */
    public function isDefined()
    {
        return $this->getField() && ($this->value_type === self::CLEAR_DATE || $this->value_type === self::FILL_CURRENT_TIME);
    }

    /**
     * Execute actions before transition happens
     *
     * @param array &$fields_data Request field data (array[field_id] => data)
     * @param PFUser  $current_user The user who are performing the update
     *
     * @return void
     */
    public function before(array &$fields_data, PFUser $current_user)
    {
        // Do something only if the value_type and the date field are properly defined
        if ($this->isDefined()) {
            $field = $this->getField();
            if ($this->value_type === self::FILL_CURRENT_TIME) {
                $new_date_timestamp = $field->formatDate($_SERVER['REQUEST_TIME']);
                if ($field->userCanRead($current_user)) {
                    $this->addFeedback(
                        'info',
                        $GLOBALS['Language']->getText(
                            'workflow_postaction',
                            'field_value_set',
                            [$field->getLabel(), $new_date_timestamp]
                        )
                    );
                }
            } else {
                $new_date_timestamp = $field->formatDate(null);
                if ($field->userCanRead($current_user)) {
                    $this->addFeedback(
                        'info',
                        $GLOBALS['Language']->getText('workflow_postaction', 'field_clear', [$field->getLabel()])
                    );
                }
            }
            $fields_data[$this->field->getId()] = $new_date_timestamp;
            $this->bypass_permissions = true;
        }
    }

    /**
     * Export postactions date to XML
     *
     * @param SimpleXMLElement &$root     the node to which the postaction is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        if ($this->getFieldId()) {
            $child = $root->addChild(Transition_PostAction_Field_Date::XML_TAG_NAME);
             $child->addAttribute('valuetype', $this->getValueType());
             $child->addChild('field_id')->addAttribute('REF', array_search($this->getFieldId(), $xmlMapping));
        }
    }

    /**
     * Wrapper for Transition_PostAction_Field_DateDao
     *
     * @return Transition_PostAction_Field_DateDao
     */
    protected function getDao()
    {
        return new Transition_PostAction_Field_DateDao();
    }

    public function accept(Visitor $visitor)
    {
        $visitor->visitDateField($this);
    }
}
