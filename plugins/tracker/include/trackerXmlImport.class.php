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

class trackerXmlImport {

    /** @var int */
    private $group_id;

    /** @var SimpleXMLElement */
    private $xml_content;

    /** @var TrackerFactory */
    private $tracker_factory;

    const XML_TRACKER_ID = '#^[T]{1}([0-9]+)$#';

    public function __construct($group_id, $xml_output, $tracker_factory) {
        $this->group_id        = $group_id;
        $this->xml_content     = simplexml_load_string($xml_output);
        $this->tracker_factory = $tracker_factory;
    }

    /**
     *
     * @return array Array of SimpleXmlElement with each tracker
     */
    public function getAllXmlTrackers() {
        $tracker_list = array();
        foreach ($this->xml_content->trackers->children() as $xml_tracker) {
            $tracker_attributes = $xml_tracker->attributes();
            $xml_tracker_id = (String) $tracker_attributes['id'];
            $tracker_list[$xml_tracker_id] = $xml_tracker;
        }
        return $tracker_list;
    }

    public function import() {
        $created_trackers = array();
        foreach ($this->getAllXmlTrackers() as $xml_tracker_id => $xml_tracker) {
            $tracker_created = $this->tracker_factory->createFromXML(
                $xml_tracker,
                $this->group_id,
                (String) $xml_tracker->name,
                (String) $xml_tracker->item_name,
                (String) $xml_tracker->description
            );

            if (! $tracker_created) {
                throw new Exception();
            }

            $created_trackers[$xml_tracker_id] = $tracker_created->getId();
        }
        return $created_trackers;
    }
}
?>
