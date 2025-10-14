<?php
/**
 * Copyright (c) Enalean SAS, 2011-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet
 *
 * ForgeUpgrade is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ForgeUpgrade is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ForgeUpgrade. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\ForgeUpgrade;

class LoggerAppenderConsoleColor
{
    public const string BLACK   = "\033[30m";
    public const string RED     = "\033[31m";
    public const string GREEN   = "\033[32m";
    public const string BLUE    = "\033[34m";
    public const string YELLOW  = "\033[35m";
    public const string BG_RED  = "\033[41m";
    public const string NOCOLOR = "\033[0m";

    /**
     * Format message aaccording to given level
     *
     * @param String $level
     * @param String $message
     *
     * @return string
     */
    public function chooseColor($level, $message)
    {
        $color = null;
        switch ($level) {
            case 'INFO':
                $color = self::GREEN;
                break;
            case 'WARN':
                $color = self::YELLOW;
                break;
            case 'ERROR':
                $color = self::RED;
                break;
            case 'FATAL':
                $color = self::BLACK . self::BG_RED;
                break;
        }
        if ($color) {
            $message = $color . $message . self::NOCOLOR;
        }
        return $message;
    }
}
