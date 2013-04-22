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

class CardwallConfigXmlImport {

    /** @var SimpleXMLElement */
    private $xml_input;

    /** @var array */
    private $mapping;

    /** @var Cardwall_OnTop_Dao */
    private $cardwall_ontop_dao;

    /** @var int */
    private $group_id;

    /** @var EventManager */
    private $event_manager;

    /**  @var XmlValidator */
    private $xml_validator;

    public function __construct($group_id, SimpleXMLElement $xml_input, array $mapping, Cardwall_OnTop_Dao $cardwall_ontop_dao, EventManager $event_manager, XmlValidator $xml_validator) {
        $this->xml_input          = $xml_input;
        $this->mapping            = $mapping;
        $this->cardwall_ontop_dao = $cardwall_ontop_dao;
        $this->group_id           = $group_id;
        $this->event_manager      = $event_manager;
        $this->xml_validator      = $xml_validator;
    }

    public function getAllTrackersId() {
        $tracker_ids = array();
        foreach ($this->xml_input->cardwall->trackers->children() as $cardwall_tracker) {
            $cardwall_tracker_xml_id = (String) $cardwall_tracker['id'];
            if (array_key_exists($cardwall_tracker_xml_id, $this->mapping)) {
                $tracker_ids[] = $this->mapping[$cardwall_tracker_xml_id];
            }
        }
        return $tracker_ids;
    }

    public function import() {

        if (! $this->xml_validator->nodeIsValid($this->xml_input->cardwall, realpath(dirname(__FILE__).'/../www/resources/xml_project_cardwall.rng'))) {
            throw new Exception();
        }

        $tracker_ids = $this->getAllTrackersId();
        foreach ($tracker_ids as $tracker_id) {
            $enabled = $this->cardwall_ontop_dao->enable($tracker_id);
            if (! $enabled) {
                throw new CardwallFromXmlImportCannotBeEnabledException($tracker_id);
            }
        }

        $this->event_manager->processEvent(
            Event::IMPORT_XML_PROJECT_CARDWALL_DONE,
            array(
                'project_id'  => $this->group_id,
                'xml_content' => $this->xml_input,
                'mapping'     => $this->mapping
            )
        );
    }
}

?>
