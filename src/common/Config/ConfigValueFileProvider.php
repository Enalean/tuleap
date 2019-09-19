<?php
/*
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class ConfigValueFileProvider implements ConfigValueProvider
{

    /** @var string */
    private $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * @see ConfigValueProvider::getVariables()
     */
    public function getVariables()
    {
        if (! is_file($this->file)) {
            return array();
        }

        include($this->file);
        return get_defined_vars();
    }
}
