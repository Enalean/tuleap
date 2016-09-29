<?php
/**
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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

use Tuleap\Project\XML\Export\ArchiveInterface;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;

class TrackerXmlExport
{
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
    private $artifact_xml_export;

    /** @var AgileDashboard_XMLExporter */
    private $agiledashboard_exporter;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var EventManager */
    private $event_manager;

    /** @var  NatureDao */
    private $nature_dao;

    public function __construct(
        TrackerFactory $tracker_factory,
        Tracker_Workflow_Trigger_RulesManager $trigger_rules_manager,
        XML_RNGValidator $rng_validator,
        Tracker_Artifact_XMLExport $artifact_xml_export,
        UserXMLExporter $user_xml_exporter,
        EventManager $event_manager,
        NatureDao $nature_dao
    ) {
        $this->tracker_factory         = $tracker_factory;
        $this->trigger_rules_manager   = $trigger_rules_manager;
        $this->rng_validator           = $rng_validator;
        $this->artifact_xml_export     = $artifact_xml_export;
        $this->user_xml_exporter       = $user_xml_exporter;
        $this->event_manager           = $event_manager;
        $this->nature_dao              = $nature_dao;
    }

    public function exportToXmlFull(
        $group_id,
        SimpleXMLElement $xml_content,
        PFUser $user,
        ArchiveInterface $archive
    ) {
        $exported_trackers = array();
        $xml_field_mapping = array();

        $xml_trackers = $xml_content->addChild('trackers');

        $this->addUsedNature($xml_content, $group_id);
        foreach ($this->tracker_factory->getTrackersByGroupId($group_id) as $tracker) {
            if ($tracker->isActive()) {
                $exported_trackers[] = $tracker;
                $artifacts = $this->exportTracker($xml_trackers, $tracker, $xml_field_mapping, $group_id);
                $this->artifact_xml_export->export($tracker, $artifacts, $user, $archive);
            }
        }

        $params = array(
            'user'        => $user,
            'xml_content' => &$xml_content,
            'group_id'    => $group_id
        );
        $this->event_manager->processEvent(TRACKER_EVENT_EXPORT_FULL_XML, $params);

        $this->exportTriggers($xml_trackers, $xml_field_mapping, $exported_trackers);
        $this->validateTrackerExport($xml_trackers);
    }

    private function addUsedNature(SimpleXMLElement $xml, $project_id)
    {
        $natures      = $xml->addChild('natures');
        $used_natures = $this->nature_dao->searchAllUsedNatureByProject($project_id);

        foreach ($used_natures as $nature) {
            if ($nature['nature']) {
                $natures->addChild('nature', $nature['nature']);
            }
        }
    }

    public function exportToXml(
        $group_id,
        SimpleXMLElement $xml_content,
        PFUser $user
    ) {
        $exported_trackers = array();
        $xml_field_mapping = array();

        $xml_trackers = $xml_content->addChild('trackers');

        $this->addUsedNature($xml_content, $group_id);
        foreach ($this->tracker_factory->getTrackersByGroupId($group_id) as $tracker) {
            if ($tracker->isActive()) {
                $exported_trackers[] = $tracker;
                $this->exportTracker($xml_trackers, $tracker, $xml_field_mapping, $group_id);
            }
        }

        $this->exportTriggers($xml_trackers, $xml_field_mapping, $exported_trackers);
        $this->validateTrackerExport($xml_trackers);
    }

    private function exportTracker(SimpleXMLElement $xml_trackers, Tracker $tracker, &$xml_field_mapping, $project_id)
    {
        $child = null;

        if ($tracker->isActive()) {
            $child = $xml_trackers->addChild('tracker');
            $tracker->exportToXML($child, $xml_field_mapping);

            if (isset($tracker) && $tracker->isProjectAllowedToUseNature()) {
                if (! isset($xml_trackers['use-natures'])) {
                    $xml_trackers->addAttribute('use-natures', 'true');
                }
            }
        }

        return $child;
    }



    private function exportTriggers(SimpleXMLElement $xml_trackers, $xml_field_mapping, $exported_trackers)
    {
        // Cross tracker stuff needs to be exported after to ensure all references exists
        $triggers_xml = $xml_trackers->addChild('triggers');
        foreach ($exported_trackers as $tracker) {
            $this->trigger_rules_manager->exportToXml($triggers_xml, $xml_field_mapping, $tracker);
        }
    }

    private function validateTrackerExport(SimpleXMLElement $xml_trackers)
    {
        try {
            $this->rng_validator->validate($xml_trackers, dirname(TRACKER_BASE_DIR).'/www/resources/trackers.rng');
            return $xml_trackers;
        } catch (XML_ParseException $exception) {
            foreach ($exception->getErrors() as $parse_error) {
                fwrite(STDERR, $parse_error.PHP_EOL);
            }
        }
    }

    public function exportSingleTrackerToXml(
        SimpleXMLElement $xml_content,
        $tracker_id,
        PFUser $user,
        Tuleap\Project\XML\Export\ArchiveInterface $archive
    ) {
        $xml_field_mapping = array();
        $xml_trackers      = $xml_content->addChild('trackers');
        $tracker           = $this->tracker_factory->getTrackerById($tracker_id);

        if ($tracker->isActive()) {
            $tracker_xml = $xml_trackers->addChild('tracker');

            $tracker->exportToXMLInProjectExportContext($tracker_xml, $this->user_xml_exporter, $xml_field_mapping);
            $this->artifact_xml_export->export($tracker, $tracker_xml, $user, $archive);
        }

        $this->rng_validator->validate($xml_trackers, dirname(TRACKER_BASE_DIR).'/www/resources/trackers.rng');
        return $xml_trackers;
    }
}
