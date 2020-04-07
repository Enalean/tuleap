<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Tracker\Semantic\IBuildSemanticFromXML;

class Tracker_TooltipFactory implements IBuildSemanticFromXML
{
    /**
     * Hold an instance of the class
     */
    protected static $instance;

    /**
     * The singleton method
     *
     * @return Tracker_TooltipFactory an instance of this factory
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            $c = self::class;
            self::$instance = new $c();
        }
        return self::$instance;
    }

    /**
     * Get an instance of Tracker_Tooltip instance form a row
     *
     * @param array   $row     The row allowing the construction of a tooltip
     * @param Tracker $tracker The tracker
     *
     * @return Tracker_Tooltip
     */
    public function getInstanceFromRow($row, $tracker)
    {
        $tooltip = new Tracker_Tooltip($tracker);
        $tooltip->setFields($row);
        return $tooltip;
    }

    public function getInstanceFromXML(SimpleXMLElement $xml, array $xml_mapping, Tracker $tracker): Tracker_Semantic
    {
        $row = array();
        foreach ($xml->field as $field) {
            $att = $field->attributes();
            if (!isset($xml_mapping[(string) $att['REF']])) {
                continue;
            }
            $row[] = $xml_mapping[(string) $att['REF']];
        }

        return $this->getInstanceFromRow($row, $tracker);
    }

    /**
     * Get the dao
     *
     * @return Tracker_TooltipDao The dao
     */
    public function getDao()
    {
        return new Tracker_TooltipDao();
    }

    /**
     * Duplicate the semantic from tracker source to tracker target
     *
     * @param int   $from_tracker_id The Id of the tracker source
     * @param int   $to_tracker_id   The Id of the tracker target
     * @param array $field_mapping   The mapping of the fields of the tracker
     *
     * @return void
     */
    public function duplicate($from_tracker_id, $to_tracker_id, $field_mapping)
    {
        $duplicator = new Tracker_Semantic_CollectionOfFieldsDuplicator($this->getDao());
        $duplicator->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
    }
}
