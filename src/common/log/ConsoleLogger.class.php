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

class Log_ConsoleLogger implements Logger
{
    public const BLACK   = "\033[30m";
    public const RED     = "\033[31m";
    public const GREEN   = "\033[32m";
    public const BLUE    = "\033[34m";
    public const YELLOW  = "\033[33m";
    public const BG_RED  = "\033[41m";
    public const NOCOLOR = "\033[0m";

    private $log = array();

    public function debug($message)
    {
        $this->log($message, Logger::DEBUG);
    }

    public function error($message, ?Exception $e = null)
    {
        $this->log($this->generateLogWithException($message, $e), Logger::ERROR);
    }

    public function info($message)
    {
        $this->log($message, Logger::INFO);
    }

    public function log($message, $level = null)
    {
        fwrite(STDERR, $this->colorize($level, $level.' '.$message).PHP_EOL);
        fflush(STDERR);
    }

    public function warn($message, ?Exception $e = null)
    {
        $this->log($this->generateLogWithException($message, $e), Logger::WARN);
    }

    private function generateLogWithException($message, ?Exception $e = null)
    {
        $log_string = $message;
        if (!empty($e)) {
            $error_message  = $e->getMessage();
            $stack_trace    = $e->getTraceAsString();
            $log_string    .= ": $error_message:\n$stack_trace";
        }
        return $log_string;
    }

    /**
     * Format message aaccording to given level
     *
     * @param String $level
     * @param String $message
     *
     * @return string
     */
    private function colorize($level, $message)
    {
        $color = null;
        switch ($level) {
            case Logger::INFO:
                $color = self::GREEN;
                break;
            case Logger::WARN:
                $color = self::YELLOW;
                break;
            case Logger::ERROR:
                $color = self::RED;
                break;
        }
        if ($color) {
            $message = $color.$message.self::NOCOLOR;
        }
        return $message;
    }
}
