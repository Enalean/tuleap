<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2010. All rights reserved
 * Copyright (c) Enalean, 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class DateHelper
{
    public const SECONDS_IN_A_DAY = 86400;

    public static function timeAgoInWords($time, $include_seconds = false, $with_title = false): string
    {
        if (! $time) {
            return '-';
        }

        $distance_of_time_in_words = self::distanceOfTimeInWords($time, $_SERVER['REQUEST_TIME'], $include_seconds);
        $str = sprintf(_('%s ago'), $distance_of_time_in_words);
        if ($time > $_SERVER['REQUEST_TIME']) {
            $str = sprintf(_('in %s'), $distance_of_time_in_words);
        }

        if ($with_title) {
            return '<span title="' . date($GLOBALS['Language']->getText('system', 'datefmt'), $time) . '">' . $str . '</span>';
        }

        return $str;
    }

    /**
     * Calculate the approximate distance between two times
     *
     * @return string
     */
    public static function distanceOfTimeInWords($from_time, $to_time, $include_seconds = false)
    {
        $distance_in_minutes = round((abs($to_time - $from_time)) / 60);
        $distance_in_seconds = round(abs($to_time - $from_time));

        return self::getFormattedDistance($distance_in_minutes, $distance_in_seconds, $include_seconds);
    }

    public static function getFormattedDistance($distance_in_minutes, $distance_in_seconds, $include_seconds)
    {
        if ($distance_in_minutes <= 1) {
            if (!$include_seconds) {
                if ($distance_in_minutes) {
                    return $GLOBALS['Language']->getText('include_utils', 'less_1_minute');
                }

                return $GLOBALS['Language']->getText('include_utils', '1_minute');
            } else {
                if ($distance_in_seconds < 1) {
                    return $GLOBALS['Language']->getText('include_utils', 'less_than_one_second', 1);
                } elseif ($distance_in_seconds < 4) {
                    return $GLOBALS['Language']->getText('include_utils', 'less_than_X_seconds', 5);
                } elseif ($distance_in_seconds < 9) {
                    return $GLOBALS['Language']->getText('include_utils', 'less_than_X_seconds', 10);
                } elseif ($distance_in_seconds < 19) {
                    return $GLOBALS['Language']->getText('include_utils', 'less_than_X_seconds', 20);
                } elseif ($distance_in_seconds < 39) {
                    return $GLOBALS['Language']->getText('include_utils', 'half_a_minute');
                } elseif ($distance_in_seconds < 59) {
                    return $GLOBALS['Language']->getText('include_utils', 'less_1_minute');
                } else {
                    return $GLOBALS['Language']->getText('include_utils', '1_minute');
                }
            }
        } elseif ($distance_in_minutes <= 44) {
            return $GLOBALS['Language']->getText('include_utils', 'X_minutes', $distance_in_minutes);
        } elseif ($distance_in_minutes <= 89) {
            return $GLOBALS['Language']->getText('include_utils', 'about_1_hour');
        } elseif ($distance_in_minutes <= 1439) {
            return $GLOBALS['Language']->getText('include_utils', 'about_X_hours', round($distance_in_minutes / 60));
        } elseif ($distance_in_minutes <= 2879) {
            return $GLOBALS['Language']->getText('include_utils', 'about_1_day');
        } elseif ($distance_in_minutes <= 43199) {
            return $GLOBALS['Language']->getText('include_utils', 'X_days', round($distance_in_minutes / 1440));
        } elseif ($distance_in_minutes <= 86399) {
            return $GLOBALS['Language']->getText('include_utils', 'about_1_month');
        } elseif ($distance_in_minutes <= 525959) {
            return $GLOBALS['Language']->getText('include_utils', 'X_months', round($distance_in_minutes / 43200));
        } elseif ($distance_in_minutes <= 1051919) {
            return $GLOBALS['Language']->getText('include_utils', 'about_1_year');
        } else {
            return $GLOBALS['Language']->getText('include_utils', 'over_X_years', round($distance_in_minutes / 525960));
        }
    }

    /**
     * Get the date in the user's expected format (depends on its locale)
     *
     * @param BaseLanguage $lang The user's language
     * @param int          $date The timestamp to transform
     * @param bool         $day_only True if display only the date, false if you want the time also
     *
     * @return string
     */
    public static function formatForLanguage(BaseLanguage $lang, $date, $day_only = false)
    {
        if ($day_only) {
            $user_date = format_date($lang->getText('system', 'datefmt_short'), $date, null);
        } else {
            $user_date = format_date($lang->getText('system', 'datefmt'), $date, null);
        }
        return $user_date;
    }

    /**
     * Returns a timestamp of the given date modifier at midnight
     *
     * @see strtotime
     *
     * @param String $date Date modifier as for 'strtotime'
     *
     * @return int
     */
    public static function getTimestampAtMidnight($date)
    {
        $time = strtotime($date);
        return mktime(0, 0, 0, date('n', $time), date('j', $time), date('Y', $time));
    }

    /**
     * Calculate difference between two dates in days
     *
     * @param int $start Timestamp of the start date
     * @param int $end Timestamp of the end date
     *
     * @return int
     */
    public static function dateDiffInDays($start, $end)
    {
        return floor(($end - $start) / self::SECONDS_IN_A_DAY);
    }

    /**
     * Decide whetehr a distance in days respects a period
     * Example: if the period is 3 the method should return true only for distances
     * that are multiples of 3 like: 3, 6, 9, 27, 501
     *
     * @param int $distance Distance in days
     * @param int $period Period to respect
     *
     * @return bool
     */
    public static function isPeriodicallyDistant($distance, $period)
    {
        return ($distance % $period == 0);
    }
}
