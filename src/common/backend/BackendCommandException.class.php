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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class BackendCommandException extends Exception
{
    private $command;
    private $output;
    private $return_value;

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

    public function getReturnValue()
    {
        return $this->return_value;
    }
}
