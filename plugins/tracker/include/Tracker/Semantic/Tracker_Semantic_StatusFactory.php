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

use Tuleap\Tracker\Semantic\IDuplicateSemantic;
use Tuleap\Tracker\Semantic\IBuildSemanticFromXML;

class Tracker_Semantic_StatusFactory implements IBuildSemanticFromXML, IDuplicateSemantic
{
    /**
     * Hold an instance of the class
     * @var self|null
     */
    protected static $instance;

    /**
     * The singleton method
     *
     * @return Tracker_Semantic_StatusFactory an instance of the factory
     */
    public static function instance()
    {
        if (! isset(self::$instance)) {
            $c              = self::class;
            self::$instance = new $c();
        }
        return self::$instance;
    }

    public function getByTracker(Tracker $tracker): Tracker_Semantic_Status
    {
        return Tracker_Semantic_Status::load($tracker);
    }

    public function getInstanceFromXML(
        SimpleXMLElement $current_semantic_xml,
        SimpleXMLElement $all_semantics_xml,
        array $xml_mapping,
        Tracker $tracker,
        array $tracker_mapping,
    ): ?Tracker_Semantic {
        $xml_field            = $current_semantic_xml->field;
        $xml_field_attributes = $xml_field->attributes();
        if (! isset($xml_mapping[(string) $xml_field_attributes['REF']])) {
            return null;
        }
        $field           = $xml_mapping[(string) $xml_field_attributes['REF']];
        $xml_open_values = $current_semantic_xml->open_values;
        $open_values     = [];
        foreach ($xml_open_values->open_value as $xml_open_value) {
            $xml_open_value_attributes = $xml_open_value->attributes();
            if (! $xml_mapping[(string) $xml_open_value_attributes['REF']]) {
                continue;
            }
            $value_id      = $xml_mapping[(string) $xml_open_value_attributes['REF']];
            $open_values[] = $value_id;
        }
        return new Tracker_Semantic_Status($tracker, $field, $open_values);
    }

    /**
     * Return the Dao
     *
     * @return Tracker_Semantic_StatusDao The dao
     */
    public function getDao()
    {
        return new Tracker_Semantic_StatusDao();
    }

    /**
     * Duplicate the semantic from tracker source to tracker target
     */
    public function duplicate(int $from_tracker_id, int $to_tracker_id, array $field_mapping): void
    {
        $dar                  = $this->getDao()->searchByTrackerId($from_tracker_id);
        $from_status_field_id = null;
        $from_open_value_ids  = [];
        // walk the semantic status rows (one row per open value)
        // to retrieve semantics values of tracker FROM
        while ($row = $dar->getRow()) {
            // if we already have the status field, just jump to open values
            if (! $from_status_field_id) {
                $from_status_field_id = $row['field_id'];
            }
            $from_open_value_ids[] = $row['open_value_id'];
        }

        // walk the mapping array to get the corresponding status values for tracker TARGET
        $to_status_field_id = false;
        $to_open_value_ids  = [];
        foreach ($field_mapping as $mapping) {
            if ($mapping['from'] == $from_status_field_id) {
                // $mapping is the mapping for the status field

                // get the field id for status field target
                $to_status_field_id = $mapping['to'];

                $mapping_values = $mapping['values'];
                // get the value ids for status open values target
                foreach ($from_open_value_ids as $from_open_value_id) {
                    if (isset($mapping_values[$from_open_value_id])) {
                        $to_open_value_ids[] = $mapping_values[$from_open_value_id];
                    }
                }
            }
        }

        if ($to_status_field_id) {
            $this->getDao()->save($to_tracker_id, $to_status_field_id, $to_open_value_ids);
        }
    }
}
