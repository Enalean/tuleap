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

class TrackerDateReminder_Logger
{
    public const LOG_INFO    = "info";
    public const LOG_WARNING = "warn";
    public const LOG_ERROR   = "error";

    private $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function info($message)
    {
        $this->log(self::LOG_INFO, $message);
    }

    public function warn($message)
    {
        $this->log(self::LOG_WARNING, $message);
    }

    public function error($message)
    {
        $this->log(self::LOG_ERROR, $message);
    }

    private function log($level, $message)
    {
        if ($this->file) {
            error_log(date('c') . " [$level] $message\n", 3, $this->file);
        }
    }
}
