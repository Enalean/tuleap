<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class SOAP_RequestLimitatorDao extends DataAccessObject
{

    public function searchFirstCallToMethod($name, $delay)
    {
        $name  = $this->da->quoteSmart($name);
        $delay = $this->da->escapeInt($delay);
        $sql   = "SELECT SQL_CALC_FOUND_ROWS *
                  FROM soap_call_counter
                  WHERE method_name = $name
                    AND date >= $delay
                  ORDER BY date ASC LIMIT 1";
        return $this->retrieve($sql);
    }

    public function saveCallToMethod($name, $time)
    {
        $name = $this->da->quoteSmart($name);
        $time = $this->da->escapeInt($time);
        $sql  = "INSERT INTO soap_call_counter (method_name, date)
                 VALUES ($name, $time)";
        return $this->update($sql);
    }
}
