<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class SVN_SvnlookException extends Exception
{
    public $command;
    public $output;
    public $returnValue;

    public function __construct($command, $output, $return_value)
    {
        $truncated_output = implode(PHP_EOL, array_slice($output, 0, 20));
        $message = 'Command execution failure: ' . $command . ' (return value: ' . $return_value . '): ' . PHP_EOL . $truncated_output;
        parent::__construct($message, $return_value);
    }
}
