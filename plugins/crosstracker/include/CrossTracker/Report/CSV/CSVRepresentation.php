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

namespace Tuleap\CrossTracker\Report\CSV;

class CSVRepresentation
{
    public const COMMA_SEPARATOR_NAME = 'comma';
    public const SEMICOLON_SEPARATOR_NAME = 'semicolon';
    public const TAB_SEPARATOR_NAME = 'tab';
    public const CSV_EMPTY_VALUE = '';

    /**
     * @var string[][]
     */
    private $values;
    /**
     * @var string
     */
    private $separator;

    public function build(array $values, \PFUser $user)
    {
        $this->values    = $values;
        $this->separator = (string) $user->getPreference('user_csv_separator');
    }

    public function __toString()
    {
        return implode($this->getSeparator(), $this->values);
    }

    private function getSeparator()
    {
        if ($this->separator === self::COMMA_SEPARATOR_NAME) {
            return ',';
        }
        if ($this->separator === self::SEMICOLON_SEPARATOR_NAME) {
            return ';';
        }
        if ($this->separator === self::TAB_SEPARATOR_NAME) {
            return "\t";
        }

        return ',';
    }
}
