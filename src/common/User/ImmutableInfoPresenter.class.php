<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class User_ImmutableInfoPresenter
{
    private $label;
    private $value;

    public function __construct($label, $value)
    {
        $this->label = $label;
        $this->value = $value;
    }

    public function label()
    {
        return $this->label;
    }

    public function value()
    {
        return $this->value;
    }

    public function can_change()
    {
        return false;
    }

    public function change()
    {
    }
}
