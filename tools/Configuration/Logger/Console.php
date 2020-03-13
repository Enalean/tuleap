<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class Console extends AbstractLogger
{
    public const RED     = "\033[31m";
    public const GREEN   = "\033[32m";
    public const YELLOW  = "\033[33m";
    public const NOCOLOR = "\033[0m";

    public function log($level, $message, array $context = array())
    {
        fwrite(STDERR, $this->colorize($level, $level . ' ' . $message) . PHP_EOL);
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
            case LogLevel::INFO:
            case LogLevel::NOTICE:
                $color = self::GREEN;
                break;
            case LogLevel::WARNING:
                $color = self::YELLOW;
                break;
            case LogLevel::ERROR:
            case LogLevel::CRITICAL:
            case LogLevel::ALERT:
            case LogLevel::EMERGENCY:
                $color = self::RED;
                break;
        }
        if ($color) {
            $message = $color . $message . self::NOCOLOR;
        }
        return $message;
    }
}
