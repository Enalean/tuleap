<?php
/**
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

class Tracker_XML_Importer_CopyArtifactInformationsAggregator implements Logger
{

    /** @var Array */
    private $logs_stack = array();

    /** @var BackendLogger */
    private $backend_logger;

    public function __construct(BackendLogger $backend_logger)
    {
        $this->backend_logger = $backend_logger;
    }

    public function getAllLogs()
    {
        return $this->logs_stack;
    }

    public function log($message, $level = Feedback::INFO)
    {
        $this->logs_stack[] = "[$level] $message";
        $this->backend_logger->log($message, $level);
    }

    public function debug($message)
    {
        $this->backend_logger->log($message, Feedback::DEBUG);
    }

    public function info($message)
    {
        $this->backend_logger->log($message, Feedback::INFO);
    }

    public function error($message, ?Exception $e = null)
    {
        $this->log($this->generateLogWithException($message, $e), Feedback::ERROR);
    }

    public function warn($message, ?Exception $e = null)
    {
        $this->log($this->generateLogWithException($message, $e), Feedback::WARN);
    }

    private function generateLogWithException($message, $e)
    {
        if (! $e) {
            return $message;
        }
        return $message . $e->getMessage();
    }
}
