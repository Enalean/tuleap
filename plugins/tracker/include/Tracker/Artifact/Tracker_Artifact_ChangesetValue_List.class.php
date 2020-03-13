<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueListFullRepresentation;

/**
 * Manage values in changeset for string fields
 */
class Tracker_Artifact_ChangesetValue_List extends Tracker_Artifact_ChangesetValue implements Countable, ArrayAccess, Iterator
{

    /**
     * @var array (of ListValue) the list of list values
     */
    protected $list_values;

    public function __construct($id, Tracker_Artifact_Changeset $changeset, $field, $has_changed, array $list_values)
    {
        parent::__construct($id, $changeset, $field, $has_changed);
        $this->list_values = $list_values;
    }

    /**
     * @return mixed
     */
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
    {
        return $visitor->visitList($this);
    }

    /**
     * spl\Countable
     *
     * @return int the number of files
     */
    public function count()
    {
        return count($this->list_values);
    }

    /**
     * spl\ArrayAccess
     *
     * @param int $offset to retrieve
     *
     * @return mixed value at given offset
     */
    public function offsetGet($offset)
    {
        return $this->list_values[$offset];
    }

    /**
     * spl\ArrayAccess
     *
     * @param int   $offset to modify
     * @param mixed $value  new value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->list_values[$offset] = $value;
    }

    /**
     * spl\ArrayAccess
     *
     * @param int $offset to check
     *
     * @return bool wether the offset exists
     */
    public function offsetExists($offset)
    {
        return isset($this->list_values[$offset]);
    }

    /**
     * spl\ArrayAccess
     *
     * @param int $offset to delete
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->files[$offset]);
    }

    /**
     * spl\Iterator
     *
     * The internal pointer to traverse the collection
     * @var int
     */
    protected $index;

    /**
     * spl\Iterator
     *
     * @return Tracker_FileInfo the current one
     */
    public function current()
    {
        return $this->list_values[$this->index];
    }

    /**
     * spl\Iterator
     *
     * @return int the current index
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * spl\Iterator
     *
     * Jump to the next Tracker_FileInfo
     *
     * @return void
     */
    public function next()
    {
        $this->index++;
    }

    /**
     * spl\Iterator
     *
     * Reset the pointer to the start of the collection
     *
     * @return Tracker_FileInfo the current one
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * spl\Iterator
     *
     * @return bool true if the current pointer is valid
     */
    public function valid()
    {
        return isset($this->list_values[$this->index]);
    }

    /**
     * Get the list values
     *
     * @return Tracker_FormElement_Field_List_BindValue[]
     */
    public function getListValues()
    {
        return $this->list_values;
    }

    /**
     * Get the value (an array of int)
     *
     * @return array of int The values of this artifact changeset value
     */
    public function getValue()
    {
        $values = $this->getListValues();
        $array = array();
        foreach ($values as $value) {
            $array[] = $value->getId();
        }
        return $array;
    }

    /**
     * Return a string that will be return in Json Format
     * as the value of this ChangesetValue_List
     *
     * @return string The value of this artifact changeset value in Json Format
     */
    public function getJsonValue()
    {
        $values = $this->getListValues();
        $returned_values = array();
        foreach ($values as $value) {
            $json_value = $value->getJsonValue();
            if ($json_value) {
                $returned_values[] = $json_value;
            }
        }
        return $returned_values;
    }

    public function getRESTValue(PFUser $user)
    {
        return $this->getFullRESTValue($user);
    }

    protected function getRESTBindValue(Tracker_FormElement_Field_List_Value $value)
    {
        return $value->getRESTId();
    }

    public function getFullRESTValue(PFUser $user)
    {
        $artifact_field_value_list_representation = new ArtifactFieldValueListFullRepresentation();
        $artifact_field_value_list_representation->build(
            $this->field->getId(),
            Tracker_FormElementFactory::instance()->getType($this->field),
            $this->field->getLabel(),
            array_values(array_map(array($this, 'getFullRESTBindValue'), $this->getListValues())),
            array_values(array_map(array($this, 'getRESTBindValue'), $this->getListValues()))
        );
        return $artifact_field_value_list_representation;
    }

    protected function getFullRESTBindValue(Tracker_FormElement_Field_List_Value $value)
    {
        return $value->getFullRESTValue($this->field);
    }

    /**
     * Get the diff between this changeset value and the one passed in param
     *
     * @return string|false The difference between another $changeset_value, false if no differneces
     */
    public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false)
    {
        $previous = $changeset_value->getListValues();
        $next     = $this->getListValues();

        if ($previous === $next) {
            return false;
        }

        $removed = $this->getRemoved($previous, $next, $format);
        $added   = $this->getAdded($previous, $next, $format);

        return $this->getChangesSentence($previous, $next, $added, $removed);
    }

    private function getAdded(array $previous, array $next, $format)
    {
        $added_elements = array_diff($next, $previous);
        $added_arr = array();
        foreach ($added_elements as $added_element) {
            /** @var Tracker_FormElement_Field_List_Value $added_element */
            $added_arr[] = $added_element->getLabel();
        }

        return $this->format(implode(', ', $added_arr), $format);
    }

    private function getRemoved(array $previous, array $next, $format)
    {
        $removed_elements = array_diff($previous, $next);
        $removed_arr = array();
        foreach ($removed_elements as $removed_element) {
            /** @var Tracker_FormElement_Field_List_Value $removed_element */
            $removed_arr[] = $removed_element->getLabel();
        }

        return $this->format(implode(', ', $removed_arr), $format);
    }

    private function format($value, $format)
    {
        if ($format === 'text') {
            return $value;
        }

        return Codendi_HTMLPurifier::instance()->purify($value);
    }

    private function getChangesSentence(array $previous, array $next, $added, $removed)
    {
        if (empty($next)) {
            return ' ' . sprintf(dgettext('tuleap-tracker', 'cleared values: %s'), $removed);
        }

        if (empty($previous)) {
            return ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'set_to') . ' ' . $added;
        }

        if (count($previous) == 1 && count($next) == 1) {
            return ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'changed_from') . ' ' . $removed
                . ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'to') . ' ' . $added;
        }

        $changes = '';
        if ($removed) {
            $changes = $removed . ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'removed');
        }
        if ($added) {
            if ($changes) {
                $changes .= PHP_EOL;
            }
            $changes .= $added . ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'added');
        }

        return $changes;
    }

    public function nodiff($format = 'html')
    {
        $next = $this->getListValues();
        $added_arr = array();
        foreach ($next as $element) {
                $added_arr[] = $element->getLabel();
        }
        return ' ' . $GLOBALS['Language']->getText('plugin_tracker_artifact', 'set_to') . ' ' . $this->format(implode(', ', $added_arr), $format);
    }
}
