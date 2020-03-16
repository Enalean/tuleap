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

class System_Command_CommandException extends RuntimeException
{
    public $command;
    public $output;
    public $return_value;

    public function __construct($command, $output, $return_value)
    {
        $this->command      = $command;
        $this->output       = $output;
        $this->return_value = $return_value;
        $message = 'Command execution failure: ' . $command . ' (return value: ' . $return_value . "):\n" . implode("\n", $output);
        parent::__construct($message, $return_value);
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return int
     */
    public function getReturnValue()
    {
        return $this->return_value;
    }
}
