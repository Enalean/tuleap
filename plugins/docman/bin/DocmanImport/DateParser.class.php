<?php
/**
 * Originally written by Clément Plantier, 2008
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class DateParser
{

    /**
     * Parse an ISO8601 calendar date
     *
     * Examples:
     *   2008-12-25T11:38:06+02:00
     *   20081225T113806+0200      (reduced format)
     *   2008-12-25T11:38:06Z      (UTC time)
     *   2008-12-25T11:38:06       (local time)
     *   2008-12-25T11:38          (without seconds)
     *
     * @return The corresponding timestamp
     */
    public static function parseIso8601($isodate)
    {
        if (preg_match("/^(\d{4})-?(\d{2})-?(\d{2})(.*)$/", $isodate, $matches)) {
            list( , $year, $month, $day, $rest) = $matches;

            if (preg_match("/^T(\d{2}):?(\d{2}):?(\d{2})?(\.\d{3})?(.*)$/", $rest, $matches)) {
                list( , $hour, $minute, $second, , $rest) = $matches;
                $offsetHour = 0;
                $offsetMinute = 0;

                $localOffset = date("Z", mktime($hour, $minute, (int) $second, $month, $day, $year));
                if (preg_match("/^([+-])(\d{2})(:?(\d{2}))$/", $rest, $matches)) {
                    list( , $sign, $offsetHour, , $offsetMinute) = $matches;
                    if ($sign == '-') {
                        $offsetHour = -$offsetHour;
                        $offsetMinute = -$offsetMinute;
                    }
                } elseif ($rest != 'Z') {
                    $localOffset = 0;
                }
            }

            return mktime($hour - $offsetHour, $minute - $offsetMinute, (int) $second, $month, $day, $year) + $localOffset;
        }
    }
}
