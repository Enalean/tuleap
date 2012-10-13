<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2010. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class DateHelper {
    
    const INCLUDE_SECONDS = 1;
    const WITH_TITLE      = 1;
    
    const SECONDS_IN_A_DAY = 86400;

    /**
     * Give the apporximate distance between a time and now
     *
     * inspired from ActionView::Helpers::DateHelper in RubyOnRails
     *
     * @return string
     */
    public static function timeAgoInWords($time, $include_seconds = false, $with_title = false) {
        $str = '-';
        if ($time) {
            $str = $GLOBALS['Language']->getText('include_utils', 'time_ago', self::distanceOfTimeInWords($time, $_SERVER['REQUEST_TIME'], $include_seconds));
            if ($with_title) {
                $str = '<span title="'. date($GLOBALS['Language']->getText('system', 'datefmt'), $time) .'">'. $str .'</span>';
            }
        }
        return $str;
    }
    
    /**
     * Calculate the approximate distance between two times
     *
     * @return string
     */
    public static function distanceOfTimeInWords($from_time, $to_time, $include_seconds = false) {    
        $distance_in_minutes = round((abs($to_time - $from_time))/60);
        $distance_in_seconds = round(abs($to_time - $from_time));
        
        if ($distance_in_minutes <= 1) {
            if (!$include_seconds) {
                return $GLOBALS['Language']->getText('include_utils', ($distance_in_minutes == 0) ? 'less_1_minute' : '1_minute');
            } else {
                if ($distance_in_seconds < 4) {
                    return $GLOBALS['Language']->getText('include_utils', 'less_than_X_seconds', 5);
                } else if ($distance_in_seconds < 9) {
                    return $GLOBALS['Language']->getText('include_utils', 'less_than_X_seconds', 10);
                } else if ($distance_in_seconds < 19) {
                    return $GLOBALS['Language']->getText('include_utils', 'less_than_X_seconds', 20);
                } else if ($distance_in_seconds < 39) {
                    return $GLOBALS['Language']->getText('include_utils', 'half_a_minute');
                } else if ($distance_in_seconds < 59) {
                    return $GLOBALS['Language']->getText('include_utils', 'less_1_minute');
                } else {
                    return $GLOBALS['Language']->getText('include_utils', '1_minute');
                }
            }
        } else if ($distance_in_minutes <= 44) {
            return $GLOBALS['Language']->getText('include_utils', 'X_minutes', $distance_in_minutes);
        } else if ($distance_in_minutes <= 89) {
            return $GLOBALS['Language']->getText('include_utils', 'about_1_hour');
        } else if ($distance_in_minutes <= 1439) {
            return $GLOBALS['Language']->getText('include_utils', 'about_X_hours', round($distance_in_minutes / 60));
        } else if ($distance_in_minutes <= 2879) {
            return $GLOBALS['Language']->getText('include_utils', 'about_1_day');
        } else if ($distance_in_minutes <= 43199) {
            return $GLOBALS['Language']->getText('include_utils', 'X_days', round($distance_in_minutes / 1440));
        } else if ($distance_in_minutes <= 86399) {
            return $GLOBALS['Language']->getText('include_utils', 'about_1_month');
        } else if ($distance_in_minutes <= 525959) {
            return $GLOBALS['Language']->getText('include_utils', 'X_months', round($distance_in_minutes / 43200));
        } else if ($distance_in_minutes <= 1051919) {
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
    public static function formatForLanguage(BaseLanguage $lang, $date, $day_only = false) {
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
     * @return Integer
     */
    public static function getTimestampAtMidnight($date) {
        $time = strtotime($date);
        return mktime(0, 0, 0, date('n', $time), date('j', $time), date('Y', $time));
    }

    /**
     * Calculate difference between two dates in days
     *
     * @param Integer $start Timestamp of the start date
     * @param Integer $end   Timestamp of the end date
     *
     * @return Integer
     */
    public static function dateDiffInDays($start, $end) {
        return floor(($end - $start) / self::SECONDS_IN_A_DAY);
    }

    /**
     * Decide whetehr a distance in days respects a period
     * Example: if the period is 3 the method should return true only for distances
     * that are multiples of 3 like: 3, 6, 9, 27, 501
     *
     * @param Integer $distance Distance in days
     * @param Integer $period   Period to respect
     *
     * @return Boolean
     */
    public static function isPeriodicallyDistant($distance, $period) {
        return ($distance % $period == 0);
    }

}

?>