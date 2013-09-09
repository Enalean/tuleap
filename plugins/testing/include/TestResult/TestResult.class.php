<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

class Testing_TestResult_TestResult {

    const NOT_RUN       = 0;
    const PASS          = 1;
    const FAIL          = 2;
    const NOT_COMPLETED = 3;

    public function __construct($status, PFUser $executed_by, $executed_on, $message) {
        $this->status      = $status;
        $this->executed_by = $executed_by;
        $this->executed_on = $executed_on;
        $this->message     = $message;
    }

    public function getStatus() { return $this->status; }
    public function getExecutedBy() { return $this->executed_by; }
    public function getExecutedOn() { return $this->executed_on; }
    public function getMessage() { return $this->message; }
}
