<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 *
 */

namespace Tuleap\Configuration\Logger;

class Console implements LoggerInterface
{
    const RED     = "\033[31m";
    const GREEN   = "\033[32m";
    const YELLOW  = "\033[33m";
    const NOCOLOR = "\033[0m";

    public function debug($message, array $context = array())
    {
        $this->log(LoggerInterface::DEBUG, $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->log(LoggerInterface::ERROR, $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->log(LoggerInterface::INFO, $message, $context);
    }

    public function warn($message, array $context = array())
    {
        $this->log(LoggerInterface::WARN, $message, $context);
    }

    public function log($level, $message, array $context = array())
    {
        fwrite(STDERR, $this->colorize($level, $level.' '.$message).PHP_EOL);
        fflush(STDERR);
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
            case LoggerInterface::INFO:
                $color = self::GREEN;
                break;
            case LoggerInterface::WARN:
                $color = self::YELLOW;
                break;
            case LoggerInterface::ERROR:
                $color = self::RED;
                break;
        }
        if ($color) {
            $message = $color.$message.self::NOCOLOR;
        }
        return $message;
    }
}
