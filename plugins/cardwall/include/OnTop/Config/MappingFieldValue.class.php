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

require_once TRACKER_BASE_DIR .'/Tracker/Tracker.class.php';
require_once TRACKER_BASE_DIR .'/Tracker/FormElement/Tracker_FormElement_Field.class.php';

class Cardwall_OnTop_Config_MappingFieldValue {

    /**
     * @var Tracker
     */
    protected $tracker;

    /**
     * @var TrackerTracker_FormElement_Field
     */
    protected $field;

    /**
     * @var int
     */
    protected $value;

    /**
     * @var int
     */
    protected $column;

    public function __construct(Tracker $tracker, Tracker_FormElement_Field $field, $value, $column) {
        $this->tracker = $tracker;
        $this->field   = $field;
        $this->value   = $value;
        $this->column  = $column;
    }

    public function getValue() {
        return $this->value;
    }

    public function getColumn() {
        return $this->column;
    }

    /**
     * @return Tracker_FormElement_Field
     */
    public function getField() {
        return $this->field;
    }

    /**
     * @return int
     */
    public function getFieldId() {
        return $this->field->getId();
    }

    /**
     * @return Tracker_FormElement_Field
     */
    public function getTracker() {
        return $this->tracker;
    }
}
?>
