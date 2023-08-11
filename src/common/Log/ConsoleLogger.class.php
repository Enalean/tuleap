<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Log_ConsoleLogger extends \Psr\Log\AbstractLogger implements \Psr\Log\LoggerInterface
{
    public const BLACK   = "\033[30m";
    public const RED     = "\033[31m";
    public const GREEN   = "\033[32m";
    public const BLUE    = "\033[34m";
    public const YELLOW  = "\033[33m";
    public const BG_RED  = "\033[41m";
    public const NOCOLOR = "\033[0m";

    public function log($level, string|\Stringable $message, array $context = []): void
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
            case \Psr\Log\LogLevel::INFO:
            case \Psr\Log\LogLevel::NOTICE:
                $color = self::GREEN;
                break;
            case \Psr\Log\LogLevel::WARNING:
                $color = self::YELLOW;
                break;
            case \Psr\Log\LogLevel::ERROR:
            case \Psr\Log\LogLevel::EMERGENCY:
            case \Psr\Log\LogLevel::ALERT:
            case \Psr\Log\LogLevel::CRITICAL:
                $color = self::RED;
                break;
        }
        if ($color) {
            $message = $color . $message . self::NOCOLOR;
        }
        return $message;
    }
}
