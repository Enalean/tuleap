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

require_once 'common/log/Logger.class.php';

class BackendLogger implements Logger {
    const FILENAME = 'codendi_syslog';
    private $filepath;

    public function __construct($filename = null) {
        $this->filepath = empty($filename) ? Config::get('codendi_log').'/'.self::FILENAME : $filename;
    }

    public function getFilepath() {
        return $this->filepath;
    }

    /**
     * @deprecated use explicit methods info, debug, ...
     *
     * Log message in codendi_syslog
     *
     * @param string $message The error message that should be logged.
     * @param string $level   The level of the message "info", "warning", ...
     *
     * @return boolean true on success or false on failure
     */
    public function log($message, $level = 'info') {
        return error_log(date('c')." [$level] $message\n", 3, $this->filepath);
    }

    public function debug($message) {
        $this->log($message, 'debug');
    }

    public function info($message) {
        $this->log($message, 'info');
    }

    public function error($message, Exception $e = null) {
        $this->log($this->generateLogWithException($message, $e), 'error');
    }

    public function warn($message, Exception $e = null) {
        $this->log($this->generateLogWithException($message, $e), 'warning');

    }

    public function generateLogWithException($message, Exception $e = null) {
        $log_string = $message;
        if (!empty($e)) {
            $error_message  = $e->getMessage();
            $stack_trace    = $e->getTraceAsString();
            $log_string    .= ": $error_message:\n$stack_trace";
        }
        return $log_string;

    }
}
?>
