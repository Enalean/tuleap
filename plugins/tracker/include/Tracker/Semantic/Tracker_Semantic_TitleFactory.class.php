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

class Tracker_Semantic_TitleFactory implements Tracker_Semantic_IRetrieveSemantic {
    
    /**
     * Hold an instance of the class
     */
    protected static $instance;
    
    /**
     * The singleton method
     *
     * @return Tracker_Semantic_TitleFactory an instance of the factory
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }
    
    public function getByTracker(Tracker $tracker) {
        return Tracker_Semantic_Title::load($tracker);
    }
    
    /**
     * Creates a Tracker_Semantic_Title Object
     * 
     * @param SimpleXMLElement $xml         containing the structure of the imported semantic title
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Tracker          $tracker     to which the semantic is attached
     * 
     * @return Tracker_Semantic_Title The semantic object
     */
    public function getInstanceFromXML($xml, &$xmlMapping, $tracker) {
        $xml_field = $xml->field;
        $xml_field_attributes = $xml_field->attributes();
        $field = $xmlMapping[(string)$xml_field_attributes['REF']];
        return new Tracker_Semantic_Title($tracker, $field);
    }
    
    /**
     * Return the Dao
     *
     * @return Tracker_Semantic_TitleDao The dao
     */
    public function getDao() {
        return new Tracker_Semantic_TitleDao();
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
    public function duplicate($from_tracker_id, $to_tracker_id, $field_mapping) {
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
?>
