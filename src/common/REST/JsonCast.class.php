<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

/**
 * This class format values for the JSON responses provided by
 * the REST routes
 */

namespace Tuleap\REST;

use DateTimeInterface;

class JsonCast
{

    /**
     * Cast a value to int if it's not null
     * @psalm-ignore-nullable-return
     * @return int|null
     */
    public static function toInt($value)
    {
        if (! is_null($value) && $value !== '') {
            return (int) $value;
        }

        return null;
    }

    /**
     * Cast a value to boolean if it's not null
     * @psalm-ignore-nullable-return
     * @return bool|null
     */
    public static function toBoolean($value)
    {
        if (! is_null($value) && $value !== '') {
            return (bool) $value;
        }

        return null;
    }

    /**
     * Cast a value to float if it's not null
     * @return float|null
     */
    public static function toFloat($value)
    {
        if (! is_null($value) && $value !== '') {
            return floatval($value);
        }

        return null;
    }

    /**
     * Cast a UNIX Timestamp to an ISO formatted date string
     * @psalm-ignore-nullable-return
     * @return string|null
     */
    public static function toDate($value)
    {
        if (! is_null($value) && $value !== '') {
            return date('c', $value);
        }

        return null;
    }

    /**
     * Cast a date time to an ISO formatted date string
     */
    public static function fromDateTimeToDate(?DateTimeInterface $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value->format('c');
    }

    /**
     * Ensure an empty array is converted to an Object Literal
     * @return array | object | null
     */
    public static function toObject($value)
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value) && empty($value)) {
            return new \stdClass();
        }

        return $value;
    }

    /**
     * @return array|null Given array where all values are casted to int.
     */
    public static function toArrayOfInts(?array $values = null)
    {
        if ($values === null) {
            return null;
        }
        return array_map(function ($value) {
            return self::toInt($value);
        }, $values);
    }
}
