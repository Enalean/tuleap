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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Log_ConsoleLogger implements Logger {

    private $log = array();

    public function debug($message) {
        $this->log($message, Logger::DEBUG);
    }

    public function error($message, Exception $e = null) {
        $this->log($this->generateLogWithException($message, $e), Logger::ERROR);
    }

    public function info($message) {
        $this->log($message, Logger::INFO);
    }

    public function log($message, $level = null) {
        $this->log[] = array($level => $message);
    }

    public function warn($message, Exception $e = null) {
        $this->log($this->generateLogWithException($message, $e), Logger::WARN);
    }

    private function generateLogWithException($message, Exception $e = null) {
        $log_string = $message;
        if (!empty($e)) {
            $error_message  = $e->getMessage();
            $stack_trace    = $e->getTraceAsString();
            $log_string    .= ": $error_message:\n$stack_trace";
        }
        return $log_string;

    }

    public function dump() {
        foreach ($this->log as $log_line) {
            list($level, $message) = each($log_line);
            fwrite(STDERR, $level.' '.$message.PHP_EOL);
        }
    }
}
