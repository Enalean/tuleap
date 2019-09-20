<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * Define an html selectbox field for Numeric/Selectbox, not-standard, not-user, fields provided by the tracker
 */
class HTML_Element_Selectbox_TrackerFields_NumericFieldsV5 extends HTML_Element_Selectbox
{

    public function __construct($tracker, $label, $name, $value, $with_none = false, $onchange = "", $desc = "")
    {
        parent::__construct($label, $name, $value, $with_none, $onchange, $desc);

        $aff = Tracker_FormElementFactory::instance();

        foreach ($aff->getUsedNumericFields($tracker) as $field) {
            if ($field->userCanRead()) {
                $selected = $this->value == $field->id;
                $this->addOption(new HTML_Element_Option($field->getLabel(), $field->id, $selected));
            }
        }
    }
}
