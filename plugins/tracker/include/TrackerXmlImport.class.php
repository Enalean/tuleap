<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'common/XmlValidator/XmlValidator.class.php';

class TrackerXmlImport {

    /** @var int */
    private $group_id;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var EventManager */
    private $event_manager;

    /** @var Tracker_Hierarchy_Dao */
    private $hierarchy_dao;

    /**  @var XmlValidator */
    private $xml_validator;


    const XML_PARENT_ID_EMPTY = "0";

    public function __construct($group_id, TrackerFactory $tracker_factory, EventManager $event_manager, Tracker_Hierarchy_Dao $hierarchy_dao, XmlValidator $xml_validator) {
        $this->group_id        = $group_id;
        $this->tracker_factory = $tracker_factory;
        $this->event_manager   = $event_manager;
        $this->hierarchy_dao   = $hierarchy_dao;
        $this->xml_validator   = $xml_validator;
    }

    /**
     *
     * @return array Array of SimpleXmlElement with each tracker
     */
    protected function getAllXmlTrackers(SimpleXMLElement $xml_input) {
        $tracker_list = array();
        foreach ($xml_input->trackers->children() as $xml_tracker) {
            $tracker_list[$this->getXmlTrackerAttribute($xml_tracker, 'id')] = $xml_tracker;
        }
        return $tracker_list;
    }

    /**
     *
     * @param SimpleXMLElement $xml_tracker
     * @param type $attribute_name
     * @return the attribute value in String, False if this attribute does not exist
     */
    private function getXmlTrackerAttribute(SimpleXMLElement $xml_tracker, $attribute_name) {
        $tracker_attributes = $xml_tracker->attributes();
        if (! $tracker_attributes[$attribute_name]) {
            return false;
        }
        return (String) $tracker_attributes[$attribute_name];
    }

    public function import(SimpleXMLElement $xml_input) {
        $created_trackers_list = array();

        foreach ($this->getAllXmlTrackers($xml_input) as $xml_tracker_id => $xml_tracker) {

            if (! $this->xml_validator->nodeIsValid($xml_tracker, realpath(TRACKER_BASE_DIR.'/../www/resources/tracker.rng'))) {
                throw new TrackerFromXmlInputNotWellFormedException();
            }

            $created_tracker       = $this->instanciateTrackerFromXml($xml_tracker_id, $xml_tracker);
            $created_trackers_list = array_merge($created_trackers_list, $created_tracker);
        }

        $this->importHierarchy($xml_input, $created_trackers_list);

        $this->event_manager->processEvent(
            Event::IMPORT_XML_PROJECT_TRACKER_DONE,
            array('project_id' => $this->group_id, 'xml_content' => $xml_input, 'mapping' => $created_trackers_list)
        );

        return $created_trackers_list;
    }

    private function importHierarchy(SimpleXMLElement $xml_input, array $created_trackers_list) {
        $all_hierarchies = array();
        foreach ($this->getAllXmlTrackers($xml_input) as $xml_tracker) {
            $all_hierarchies = $this->buildTrackersHierarchy($all_hierarchies, $xml_tracker, $created_trackers_list);
        }

        $this->storeHierarchyInDB($all_hierarchies);
    }

    /**
     *
     * @param type $xml_tracker_id
     * @param SimpleXMLElement $xml_tracker
     * @return array the link between xml id and new id given by Tuleap
     * @throws TrackerFromXmlImportCannotBeCreatedException
     */
    private function instanciateTrackerFromXml($xml_tracker_id, SimpleXMLElement $xml_tracker) {
        $tracker_created = $this->tracker_factory->createFromXML(
               $xml_tracker,
               $this->group_id,
               (String) $xml_tracker->name,
               (String) $xml_tracker->description,
               (String) $xml_tracker->item_name
        );

        if (! $tracker_created) {
            throw new TrackerFromXmlImportCannotBeCreatedException((String) $xml_tracker->name);
        }

        return array($xml_tracker_id => $tracker_created->getId());
    }

    /**
     *
     * @param array $hierarchy
     * @param SimpleXMLElement $xml_tracker
     * @param array $mapper
     * @return array The hierarchy array with new elements added
     */
    protected function buildTrackersHierarchy(array $hierarchy, SimpleXMLElement $xml_tracker, array $mapper) {
        $xml_parent_id = $this->getXmlTrackerAttribute($xml_tracker, 'parent_id');

        if ($xml_parent_id != self::XML_PARENT_ID_EMPTY) {
            $parent_id  = $mapper[$xml_parent_id];
            $tracker_id = $mapper[$this->getXmlTrackerAttribute($xml_tracker, 'id')];

            if (! isset($hierarchy[$parent_id])) {
                $hierarchy[$parent_id] = array();
            }

            array_push($hierarchy[$parent_id], $tracker_id);
        }

        return $hierarchy;
    }

    /**
     *
     * @param array $all_hierarchies
     *
     * Stores in database the hierarchy between created trackers
     */
    public function storeHierarchyInDB(array $all_hierarchies) {
        foreach ($all_hierarchies as $parent_id => $hierarchy) {
            $this->hierarchy_dao->updateChildren($parent_id, $hierarchy);
        }
     }
}
?>
