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

namespace Tuleap\Tracker\FormElement\Field\Date;

class CSVFormatter
{
    public const MONTH_DAY_YEAR = 'month_day_year';
    public const DAY_MONTH_YEAR = 'day_month_year';

    /**
     * @param int     $date
     * @param bool    $is_time_shown
     * @return string
     */
    public function formatDateForCSVForUser(\PFUser $user, $date, $is_time_shown)
    {
        $date_format = $user->getPreference("user_csv_dateformat");
        $format      = $this->getCSVDateFormat($date_format, $is_time_shown);

        return format_date($format, (float) $date, '');
    }

    private function getCSVDateFormat($user_preferred_date_format, $is_time_shown)
    {
        if ($user_preferred_date_format === self::MONTH_DAY_YEAR) {
            $format = 'm/d/Y';
        } elseif ($user_preferred_date_format === self::DAY_MONTH_YEAR) {
            $format = 'd/m/Y';
        } else {
            $format = 'm/d/Y';
        }

        if ($is_time_shown === true) {
            $format .= ' H:i';
        }

        return $format;
    }
}
