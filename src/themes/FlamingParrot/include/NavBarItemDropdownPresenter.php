<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class FlamingParrot_NavBarItemDropdownPresenter extends FlamingParrot_NavBarItemPresenter
{

    public $is_dropdown = true;

    public $label;

    /** @var FlamingParrot_NavBarItemDropdownSectionPresenter[] */
    public $sections = array();

    public function __construct($id, $is_active, $label)
    {
        parent::__construct($id, $is_active);
        $this->label = $label;
    }

    public function addSection(FlamingParrot_NavBarItemDropdownSectionPresenter $section)
    {
        $previous_section = end($this->sections);
        if ($previous_section) {
            $previous_section->flagAsNotLastSection();
        }

        $this->sections[] = $section;
    }
}
