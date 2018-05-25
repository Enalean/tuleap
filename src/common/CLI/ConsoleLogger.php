<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CLI;

use Exception;
use Logger;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleLogger implements Logger
{
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function debug($message)
    {
        $this->log($message, Logger::DEBUG);
    }

    public function error($message, Exception $e = null)
    {
        $this->log($this->generateLogWithException($message, $e), Logger::ERROR);
    }

    public function info($message)
    {
        $this->log($message, Logger::INFO);
    }

    public function log($message, $level = null)
    {
        $this->output->writeln($this->colorize($level, $message));
    }

    public function warn($message, Exception $e = null)
    {
        $this->log($this->generateLogWithException($message, $e), Logger::WARN);
    }

    private function generateLogWithException($message, Exception $e = null)
    {
        $log_string = $message;
        if ($e !== null) {
            $error_message = $e->getMessage();
            $stack_trace   = $e->getTraceAsString();
            $log_string    .= ": $error_message:\n$stack_trace";
        }
        return $log_string;
    }

    private function colorize($level, $message)
    {
        $color = null;
        switch ($level) {
            case Logger::INFO:
                return '<info>' . OutputFormatter::escape($message) . '</info>';
            case Logger::WARN:
                return '<fg=yellow>' . OutputFormatter::escape($message) . '</fg>';
            case Logger::ERROR:
                return '<error>' . OutputFormatter::escape($message) . '</error>';
            default:
                return OutputFormatter::escape($message);
        }
    }
}
