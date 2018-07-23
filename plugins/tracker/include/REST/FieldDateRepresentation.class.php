<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

class Tracker_REST_FieldDateRepresentation extends Tracker_REST_FieldRepresentation {

    /**
     * @var bool
     */
    public $is_time_displayed;

    public function build(Tracker_FormElement $field, $type, array $permissions) {
        parent::build($field, $type, $permissions);

        if ($field instanceof Tracker_FormElement_Field_Date) {
            $this->is_time_displayed = $field->isTimeDisplayed();
        }
    }
}
