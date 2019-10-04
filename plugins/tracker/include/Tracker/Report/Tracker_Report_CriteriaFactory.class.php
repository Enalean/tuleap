<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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


class Tracker_Report_CriteriaFactory
{

    /**
     * A protected constructor; prevents direct creation of object
     */
    protected function __construct()
    {
    }

    /**
     * Hold an instance of the class
     */
    protected static $_instance;

    /**
     * The singleton method
     */
    public static function instance()
    {
        if (!isset(self::$_instance)) {
            $c = self::class;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }

    /**
     * @param array the row allowing the construction of a criteria
     * @return Criteria Object
     */
    public function getInstanceFromRow($row)
    {
        return new Tracker_Report_Criteria(
            $row['id'],
            $row['report'],
            $row['field'],
            $row['rank'],
            $row['is_advanced']
        );
    }

    /**
     * Creates a Tracker_Report_Criteria Object
     *
     * @param SimpleXMLElement $xml         containing the structure of the imported criteria
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     *
     * @return null | Tracker_Report_Criteria Object
     */
    public function getInstanceFromXML($xml, &$xmlMapping)
    {
        $att  = $xml->attributes();
        $fatt = $xml->field->attributes();
        if (! isset($xmlMapping[(string)$fatt['REF']])) {
            return null;
        }
        $row                = array(
            'field' => $xmlMapping[(string)$fatt['REF']],
            'rank' => (int)$att['rank']
        );
        $row['is_advanced'] = isset($att['is_advanced']) ? (int)$att['is_advanced'] : 0;
        // in case old id values are important modify code here
        if (false) {
            foreach ($xml->attributes() as $key => $value) {
                $row[$key] = (int)$value;
            }
        } else {
            $row['id']     = 0;
            $row['report'] = null;
        }

        return $this->getInstanceFromRow($row);
    }

    public function duplicate($from_report, $to_report, $fields_mapping)
    {
        $this->getDao()->duplicate($from_report->id, $to_report->id, $fields_mapping);
    }

    public function saveObject($criteria)
    {
    }

    protected function getDao()
    {
        return new Tracker_Report_CriteriaDao();
    }
}
