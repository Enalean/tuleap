<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\Grammar;

use DateInterval;
use DateTime;

class CurrentDateTimeValueWrapper implements ValueWrapper
{
    private static $MINUS_SIGN = '-';

    /**
     * @var DateTime
     */
    private $value;

    public function __construct($sign, $period)
    {
        $this->value = $this->computeCurrentDateTime($sign, $period);
    }

    public function accept(ValueWrapperVisitor $visitor)
    {
        return $visitor->visitCurrentDateTimeValueWrapper($this);
    }

    /**
     * @return DateTime
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return DateTime
     */
    private function computeCurrentDateTime($sign, $period)
    {
        $value = new DateTime();

        if ($period) {
            if ($sign === self::$MINUS_SIGN) {
                $value->sub(new DateInterval($period));
            } else {
                $value->add(new DateInterval($period));
            }
        }

        return $value;
    }
}
