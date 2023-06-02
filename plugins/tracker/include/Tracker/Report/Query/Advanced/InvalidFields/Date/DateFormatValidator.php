<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\Date;

use DateTime;
use Tuleap\Tracker\Report\Query\Advanced\DateFormat;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\EmptyStringChecker;

class DateFormatValidator
{
    public function __construct(private readonly EmptyStringChecker $empty_string_checker, private readonly string $default_format)
    {
    }

    /**
     * @throws DateToEmptyStringException
     * @throws DateToStringException
     */
    public function checkValueIsValid($value)
    {
        $date_value = $this->getDateTimeFromValue($value);

        if ($this->empty_string_checker->isEmptyStringAProblem($value)) {
            throw new DateToEmptyStringException();
        }

        if ($date_value === false && $value !== '') {
            throw new DateToStringException($value);
        }
    }

    /**
     * @param $value
     * @return DateTime
     */
    private function getDateTimeFromValue($value)
    {
        if ($this->shouldTryDateTimeFormatFirst()) {
            $date_value = DateTime::createFromFormat(DateFormat::DATETIME, $value);
            if ($date_value !== false) {
                return $date_value;
            }
        }

        $date_value = DateTime::createFromFormat(DateFormat::DATE, $value);

        return $date_value;
    }

    /**
     * @return bool
     */
    private function shouldTryDateTimeFormatFirst()
    {
        return $this->default_format === DateFormat::DATETIME;
    }
}
