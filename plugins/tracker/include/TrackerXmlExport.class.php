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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class TrackerXmlExport {

    /**
     * @var UserXMLExporter
     */
    private $user_xml_exporter;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var Tracker_Workflow_Trigger_RulesManager */
    private $trigger_rules_manager;

    /** @var XML_RNGValidator */
    private $rng_validator;

    /** @var Tracker_Artifact_XMLExport */
    private $artifact_xml_xport;

    public function __construct(
        TrackerFactory $tracker_factory,
        Tracker_Workflow_Trigger_RulesManager $trigger_rules_manager,
        XML_RNGValidator $rng_validator,
        Tracker_Artifact_XMLExport $artifact_xml_export,
        UserXMLExporter $user_xml_exporter
    ) {
        $this->tracker_factory       = $tracker_factory;
        $this->trigger_rules_manager = $trigger_rules_manager;
        $this->rng_validator         = $rng_validator;
        $this->artifact_xml_xport    = $artifact_xml_export;
        $this->user_xml_exporter     = $user_xml_exporter;
    }

    public function exportToXml($group_id, SimpleXMLElement $xml_content) {
        $exported_trackers = array();
        $xml_field_mapping = array();

        $xml_trackers = $xml_content->addChild('trackers');

        foreach ($this->tracker_factory->getTrackersByGroupId($group_id) as $tracker) {
            if ($tracker->isActive()) {
                $exported_trackers[] = $tracker;
                $child = $xml_trackers->addChild('tracker');
                $tracker->exportToXML($child, $xml_field_mapping);
            }
        }

        // Cross tracker stuff needs to be exported after to ensure all references exists
        $triggers_xml = $xml_trackers->addChild('triggers');
        foreach ($exported_trackers as $tracker) {
            $this->trigger_rules_manager->exportToXml($triggers_xml, $xml_field_mapping, $tracker);
        }

        try {
            $this->rng_validator->validate($xml_trackers, dirname(TRACKER_BASE_DIR).'/www/resources/trackers.rng');
            return $xml_trackers;
        } catch (XML_ParseException $exception) {
            foreach ($exception->getErrors() as $parse_error) {
                fwrite(STDERR, $parse_error.PHP_EOL);
            }
        }
    }

    public function exportSingleTrackerToXml(SimpleXMLElement $xml_content, $tracker_id, PFUser $user, ZipArchive $archive) {
        $xml_field_mapping = array();
        $xml_trackers      = $xml_content->addChild('trackers');
        $tracker           = $this->tracker_factory->getTrackerById($tracker_id);

        if ($tracker->isActive()) {
            $tracker_xml = $xml_trackers->addChild('tracker');

            $tracker->exportToXMLInProjectExportContext($tracker_xml, $this->user_xml_exporter, $xml_field_mapping);
            $this->artifact_xml_xport->export($tracker, $tracker_xml, $user, $archive);
        }

        $this->rng_validator->validate($xml_trackers, dirname(TRACKER_BASE_DIR).'/www/resources/trackers.rng');
        return $xml_trackers;
    }
}
