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

namespace Tuleap\CrossTracker\Report\CSV\Format;

class DateValue implements ValueVisitable
{
    /** @var int */
    private $value;
    /** @var bool */
    private $is_time_shown;

    /**
     * @param bool $is_time_shown
     */
    public function __construct($is_time_shown)
    {
        $this->is_time_shown = $is_time_shown;
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isTimeShown()
    {
        return $this->is_time_shown;
    }

    public function accept(FormatterVisitor $visitor, FormatterParameters $parameters)
    {
        return $visitor->visitDateValue($this, $parameters);
    }

    /**
     * @param int $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
