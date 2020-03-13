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

// This is an on going work to help developers to build more expressive tests
// please add the functions/methods below when needed.
// For further information about the Test Data Builder pattern
// @see http://nat.truemesh.com/archives/000727.html
require_once __DIR__ . '/../bootstrap.php';

function aTextField()
{
    return new Test_Tracker_FormElement_Builder('Tracker_FormElement_Field_Text');
}

function anIntegerField()
{
    return new Test_Tracker_FormElement_Builder('Tracker_FormElement_Field_Integer');
}

function aFloatField()
{
    return new Test_Tracker_FormElement_Builder('Tracker_FormElement_Field_Float');
}

function aStringField()
{
    return new Test_Tracker_FormElement_Builder('Tracker_FormElement_Field_String');
}

function aDateFieldWithTime()
{
    return new Test_Tracker_FormElementDate_Builder('Tracker_FormElement_Field_Date', true);
}

function aMockDateWithoutTimeField()
{
    return new Test_Tracker_FormElementDate_Builder('Tracker_FormElement_Field_Date', false);
}

function anOpenListField()
{
    return new Test_Tracker_FormElement_Builder('Tracker_FormElement_Field_OpenList');
}

function anArtifactLinkField()
{
    return new Test_Tracker_FormElement_Builder('Tracker_FormElement_Field_ArtifactLink');
}

function aSelectBoxField()
{
    return new Test_Tracker_FormElement_Builder('Tracker_FormElement_Field_Selectbox');
}

function aMultiSelectBoxField()
{
    return new Test_Tracker_FormElement_Builder('Tracker_FormElement_Field_MultiSelectbox');
}

function aRadiobuttonField()
{
    return new Test_Tracker_FormElement_Builder('Tracker_FormElement_Field_Radiobutton');
}

function aFileField()
{
    return new Test_Tracker_FormElement_Builder('Tracker_FormElement_Field_File');
}

class Test_Tracker_FormElement_Builder
{
    protected $klass;
    protected $id;
    protected $name;
    protected $tracker;
    protected $trackerId;
    protected $originalField;
    protected $use_it;
    protected $bind;
    protected $label;
    private $properties = array();

    public function __construct($klass)
    {
        $this->klass = $klass;
    }

    public function withName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function withTracker($tracker)
    {
        $this->tracker   = $tracker;
        return $this;
    }

    public function withTrackerId($trackerId)
    {
        $this->trackerId = $trackerId;
        return $this;
    }

    public function isUsed()
    {
        $this->use_it = true;
        return $this;
    }

    /**
     * @only for Tracker_FormElement_Field_List
     */
    public function withBind($bind)
    {
        $this->bind = $bind;
        return $this;
    }

    public function withLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function withProperty($name, $type, $value)
    {
        $this->properties[$name] = array(
            'name'  => $name,
            'type'  => $type,
            'value' => $value
        );

        return $this;
    }

    protected function setProperties(Tracker_FormElement_Field $field)
    {
        if ($this->properties) {
            $field->setCacheSpecificProperties($this->properties);
        }
    }

    /**
     * @return Tracker_FormElement
     */
    public function build()
    {
        $klass  = $this->klass;
        $object = new $klass($this->id, $this->trackerId, null, $this->name, $this->label, null, $this->use_it, null, null, null, null, $this->originalField);
        if ($this->tracker) {
            $object->setTracker($this->tracker);
        }
        if ($this->bind) {
            $object->setBind($this->bind);
        }
        $this->setProperties($object);

        return $object;
    }
}
class Test_Tracker_FormElementDate_Builder extends Test_Tracker_FormElement_Builder
{
    protected $with_time;

    public function __construct($klass, $with_time)
    {
        $this->with_time = $with_time;
    }

    public function build()
    {
        $object = partial_mock(
            'Tracker_FormElement_Field_Date',
            array('isTimeDisplayed'),
            array($this->id, $this->trackerId, null, $this->name, $this->label, null, $this->use_it, null, null, null, null, $this->originalField)
        );

        stub($object)->isTimeDisplayed()->returns($this->with_time);

        if ($this->tracker) {
            $object->setTracker($this->tracker);
        }
        if ($this->bind) {
            $object->setBind($this->bind);
        }
        $this->setProperties($object);

        return $object;
    }
}
