<?php
/**
  * Copyright (c) Enalean, 2012. All rights reserved
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

/**
 * Date Rule between two dynamic fields
 *
 * For a tracker, if a source field is selected to a specific value,
 * then target field will be constrained to another value.
 *
 */
class Tracker_Rule_Date extends Tracker_Rule
{

    public const COMPARATOR_EQUALS                 = '=';
    public const COMPARATOR_NOT_EQUALS             = '≠';
    public const COMPARATOR_LESS_THAN              = '<';
    public const COMPARATOR_LESS_THAN_OR_EQUALS    = '≤';
    public const COMPARATOR_GREATER_THAN           = '>';
    public const COMPARATOR_GREATER_THAN_OR_EQUALS = '≥';

    public static $allowed_comparators = array(
        self::COMPARATOR_LESS_THAN,
        self::COMPARATOR_LESS_THAN_OR_EQUALS,
        self::COMPARATOR_EQUALS,
        self::COMPARATOR_GREATER_THAN_OR_EQUALS,
        self::COMPARATOR_GREATER_THAN,
        self::COMPARATOR_NOT_EQUALS,
    );

    /**
     *
     * @var string
     */
    protected $comparator;

    /**
     *
     * @param string $comparator
     * @throws Tracker_Rule_Date_InvalidComparatorException
     */
    public function setComparator($comparator)
    {
        if (! in_array($comparator, self::$allowed_comparators)) {
            throw new Tracker_Rule_Date_InvalidComparatorException();
        }

        $this->comparator = $comparator;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getComparator()
    {
        return $this->comparator;
    }

    /**
     *
     * Checks that two given values satisfy the rule
     *
     * @param string $source_value
     * @param string $target_value
     * @return bool
     */
    public function validate($source_value, $target_value)
    {
        //if one of the value is empty then return true
        if ($source_value == null || $target_value == null) {
            return true;
        }

        $date_only   = $this->isOneValueDateOnly($source_value, $target_value);

        $source_date = $this->getTimestamp($source_value, $date_only);
        $target_date = $this->getTimestamp($target_value, $date_only);

        switch ($this->getComparator()) {
            case self::COMPARATOR_EQUALS:
                return $source_date == $target_date;
            case self::COMPARATOR_NOT_EQUALS:
                return $source_date != $target_date;
            case self::COMPARATOR_GREATER_THAN:
                return $source_date > $target_date;
            case self::COMPARATOR_GREATER_THAN_OR_EQUALS:
                return $source_date >= $target_date;
            case self::COMPARATOR_LESS_THAN:
                return $source_date < $target_date;
            case self::COMPARATOR_LESS_THAN_OR_EQUALS:
                return $source_date <= $target_date;
            default:
                throw new Tracker_Rule_Date_MissingComparatorException();
        }
    }

    private function isOneValueDateOnly($source_value, $target_value)
    {
        return (preg_match(Rule_Date::DAY_REGEX, $source_value) || preg_match(Rule_Date::DAY_REGEX, $target_value));
    }

    private function getTimestamp($date, $date_only)
    {
        if (preg_match(Rule_Timestamp::TIMESTAMP_REGEX, $date) && $date_only) {
            //transform timestamps for "submitted on" and "last updated date"
            $date = date(Tracker_FormElement_DateFormatter::DATE_FORMAT, $date);
        } elseif (preg_match(Rule_Timestamp::TIMESTAMP_REGEX, $date)) {
            return $date;
        }

        if (preg_match(Rule_Date_Time::DAYTIME_REGEX, $date, $matches)) {
            if ($date_only) {
                return mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
            }
            return mktime($matches[4], $matches[5], 0, $matches[2], $matches[3], $matches[1]);
        } elseif (preg_match(Rule_Date::DAY_REGEX, $date, $matches)) {
            return mktime(0, 0, 0, $matches[2], $matches[3], $matches[1]);
        }
    }
}
