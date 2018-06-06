<?php
/**
* Copyright Enalean (c) 2013 - 2018. All rights reserved.
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

use Tuleap\Cardwall\Semantic\CardFieldXmlExtractor;

class Cardwall_Semantic_CardFieldsFactory implements Tracker_Semantic_IRetrieveSemantic {

    /**
     * Hold an instance of the class
     */
    protected static $instance;

    /**
     * The singleton method
     *
     * @return Cardwall_Semantic_CardFieldsFactory an instance of the factory
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            $class_name = __CLASS__;
            self::$instance = new $class_name;
        }
        return self::$instance;
    }

    /**
     * @return Cardwall_Semantic_CardFields
     */
    public function getByTracker(Tracker $tracker) {
        return Cardwall_Semantic_CardFields::load($tracker);
    }

    /**
     * Creates a Cardwall_Semantic_CardFields Object
     *
     * @param SimpleXMLElement $xml          containing the structure of the imported semantic initial effort
     * @param array            &$xml_mapping containig the newly created formElements idexed by their XML IDs
     * @param Tracker          $tracker      to which the semantic is attached
     *
     * @return Cardwall_Semantic_CardFields The semantic object
     */
    public function getInstanceFromXML($xml, &$xml_mapping, $tracker)
    {
        $extractor        = new CardFieldXmlExtractor();
        $fields           = $extractor->extractFieldFromXml($xml, $xml_mapping);
        $background_color = $extractor->extractBackgroundColorFromXml($xml, $xml_mapping);

        $semantic = Cardwall_Semantic_CardFields::load($tracker);
        $semantic->setFields($fields);
        $semantic->setBackgroundColorField($background_color);

        return $semantic;
    }

    /**
     * Return the Dao
     *
     * @return Cardwall_Semantic_Dao_CardFieldsDao The dao
     */
    public function getDao() {
        return new Cardwall_Semantic_Dao_CardFieldsDao();
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
        $duplicator = new Tracker_Semantic_CollectionOfFieldsDuplicator($this->getDao());
        $duplicator->duplicate($from_tracker_id, $to_tracker_id, $field_mapping);
    }
}
