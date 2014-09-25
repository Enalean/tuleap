<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class InvalidTransition implements Transition_Interface {

    /**
     * @var Tracker_FormElement_Field_List_Value
     */
    protected $from;

    /**
     * @var Tracker_FormElement_Field_List_Value
     */
    protected $to;

    public function __construct($from, $to) {
        $this->from = $from;
        $this->to   = $to;
    }

    /**
     * Access From field value
     *
     * @return Tracker_FormElement_Field_List_Value
     */
    public function getFieldValueFrom() {
        return $this->from;
    }

    /**
     * Access To field value
     *
     * @return Tracker_FormElement_Field_List_Value
     */
    public function getFieldValueTo() {
        return $this->to;
    }

    public function validate($fields_data, Tracker_Artifact $artifact) {
        return false;
    }

    public function before(&$fields_data, PFUser $current_user) {
    }

    public function after(Tracker_Artifact_Changeset $changeset) {
    }
}