<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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
 * Define an html selectbox field for scale (day, week, month, year)
 */
class HTML_Element_Selectbox_Scale extends HTML_Element_Selectbox
{

    public function __construct($label, $name, $value, $with_none = false, $onchange = "", $desc = "")
    {
        parent::__construct($label, $name, $value, $with_none, $onchange, $desc);

        foreach (array('day', 'week', 'month', 'year') as $scale) {
            $selected = $this->value == $scale;
            $this->addOption(
                new HTML_Element_Option(
                    $this->getOptionLabel($scale),
                    $scale,
                    $selected
                )
            );
        }
    }

    private function getOptionLabel($scale)
    {
        if ($scale === 'day') {
            return _('Day');
        } elseif ($scale === 'week') {
            return _('Week');
        } elseif ($scale === 'month') {
            return _('Month');
        } elseif ($scale === 'year') {
            return _('Year');
        }

        return '';
    }
}
