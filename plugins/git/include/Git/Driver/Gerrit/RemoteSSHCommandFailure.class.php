<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Git_Driver_Gerrit_RemoteSSHCommandFailure extends Exception {

    private $exit_code;
    private $std_out;
    private $std_err;

    function __construct($exit_code, $std_out, $std_err) {
        parent::__construct(implode(PHP_EOL, array("exit_code: $exit_code", "std_err: $std_err", "std_out: $std_out")));
        $this->exit_code = $exit_code;
        $this->std_out = $std_out;
        $this->std_err = $std_err;
    }

    /**
     * @return int
     */
    public function getExitCode() {
        return $this->exit_code;
    }

    /**
     * @return string with PHP_EOL as line terminators
     */
    public function getStdErr() {
        return $this->std_err;
    }

    /**
     * @return string with PHP_EOL as line terminators
     */
    public function getStdOut() {
        return $this->std_out;
    }
}
?>
