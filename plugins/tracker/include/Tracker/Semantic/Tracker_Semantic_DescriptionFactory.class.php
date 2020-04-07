<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Tracker\Semantic\IDuplicateSemantic;
use Tuleap\Tracker\Semantic\IBuildSemanticFromXML;

class Tracker_Semantic_DescriptionFactory implements IBuildSemanticFromXML, IDuplicateSemantic
{

    /**
     * Hold an instance of the class
     */
    protected static $instance;

    /**
     * The singleton method
     *
     * @return Tracker_Semantic_TitleFactory an instance of the factory
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            $c = self::class;
            self::$instance = new $c();
        }
        return self::$instance;
    }

    public function getByTracker(Tracker $tracker)
    {
        return Tracker_Semantic_Description::load($tracker);
    }

    public function getInstanceFromXML(SimpleXMLElement $xml, array $xml_mapping, Tracker $tracker): ?Tracker_Semantic
    {
        $xml_field = $xml->field;
        $xml_field_attributes = $xml_field->attributes();
        if (! isset($xml_mapping[(string) $xml_field_attributes['REF']])) {
            return null;
        }
        $field = $xml_mapping[(string) $xml_field_attributes['REF']];
        return new Tracker_Semantic_Description($tracker, $field);
    }

    /**
     * Return the Dao
     *
     * @return Tracker_Semantic_DescriptionDao The dao
     */
    public function getDao()
    {
        return new Tracker_Semantic_DescriptionDao();
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
    public function duplicate($from_tracker_id, $to_tracker_id, array $field_mapping)
    {
        $row = $this->getDao()->searchByTrackerId($from_tracker_id)->getRow();
        if ($row) {
            $from_title_field_id = $row['field_id'];
            $to_title_field_id = false;
            foreach ($field_mapping as $mapping) {
                if ($mapping['from'] == $from_title_field_id) {
                    $to_title_field_id = $mapping['to'];
                }
            }
            if ($to_title_field_id) {
                $this->getDao()->save($to_tracker_id, $to_title_field_id);
            }
        }
    }
}
