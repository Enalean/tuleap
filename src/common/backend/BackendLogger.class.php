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

class BackendLogger implements Logger
{
    public const FILENAME = 'codendi_syslog';

    private $filepath;

    public function __construct($filename = null)
    {
        $this->filepath = empty($filename) ? ForgeConfig::get('codendi_log').'/'.self::FILENAME : $filename;
    }

    /**
     * @return Logger
     */
    public static function getDefaultLogger()
    {
        return new TruncateLevelLogger(
            new BackendLogger(),
            ForgeConfig::get('sys_logger_level')
        );
    }

    public function getFilepath()
    {
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
     * @return bool true on success or false on failure
     */
    public function log($message, $level = Feedback::INFO)
    {
        $pid = getmypid();

        return error_log(date('c')." [$pid] [$level] $message\n", 3, $this->filepath);
    }

    public function debug($message)
    {
        $this->log($message, Feedback::DEBUG);
    }

    public function info($message)
    {
        $this->log($message, Feedback::INFO);
    }

    public function error($message, ?Exception $e = null)
    {
        $this->log($this->generateLogWithException($message, $e), Feedback::ERROR);
    }

    public function warn($message, ?Exception $e = null)
    {
        $this->log($this->generateLogWithException($message, $e), Feedback::WARN);
    }

    public function generateLogWithException($message, ?Exception $e = null)
    {
        $log_string = $message;
        if (!empty($e)) {
            $error_message  = $e->getMessage();
            $stack_trace    = $e->getTraceAsString();
            $log_string    .= ": $error_message:\n$stack_trace";
        }
        return $log_string;
    }
}
