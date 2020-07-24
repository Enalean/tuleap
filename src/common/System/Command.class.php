<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

class System_Command
{

    /**
     * @throws System_Command_CommandException
     * @param string $cmd
     * @return array
     */
    public function exec($cmd)
    {
        $return_value = 1;
        $output       = [];
        exec("$cmd 2>&1", $output, $return_value);
        if ($return_value == 0) {
            return $output;
        } else {
            throw new System_Command_CommandException($cmd, $output, $return_value);
        }
    }
}
