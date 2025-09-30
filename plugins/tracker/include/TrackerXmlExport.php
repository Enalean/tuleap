<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\XML\Exporter\TrackerEventExportFullXML;
use Tuleap\Tracker\Artifact\XML\Exporter\TrackerEventExportStructureXML;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Tracker;

class TrackerXmlExport // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
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

    /** @var EventManager */
    private $event_manager;

    /** @var  TypePresenterFactory */
    private $nature_presenter_factory;

    /**
     * @var ArtifactLinksUsageDao
     */
    private $artifact_links_usage_dao;
    /**
     * @var ExternalFieldsExtractor
     */
    private $external_field_extractor;

    public function __construct(
        TrackerFactory $tracker_factory,
        Tracker_Workflow_Trigger_RulesManager $trigger_rules_manager,
        XML_RNGValidator $rng_validator,
        Tracker_Artifact_XMLExport $artifact_xml_export,
        UserXMLExporter $user_xml_exporter,
        EventManager $event_manager,
        TypePresenterFactory $nature_presenter_factory,
        ArtifactLinksUsageDao $artifact_links_usage_dao,
        ExternalFieldsExtractor $external_field_extractor,
        private readonly \Psr\Log\LoggerInterface $logger,
    ) {
        $this->tracker_factory          = $tracker_factory;
        $this->trigger_rules_manager    = $trigger_rules_manager;
        $this->rng_validator            = $rng_validator;
        $this->artifact_xml_export      = $artifact_xml_export;
        $this->user_xml_exporter        = $user_xml_exporter;
        $this->event_manager            = $event_manager;
        $this->nature_presenter_factory = $nature_presenter_factory;
        $this->artifact_links_usage_dao = $artifact_links_usage_dao;
        $this->external_field_extractor = $external_field_extractor;
    }

    public function exportToXmlFull(
        Project $project,
        SimpleXMLElement $xml_content,
        PFUser $user,
        ArchiveInterface $archive,
    ) {
        $exported_trackers = [];
        $xml_field_mapping = [];

        $xml_trackers = $xml_content->addChild('trackers');

        $this->addUsedTypes($xml_content, $project);
        foreach ($this->tracker_factory->getTrackersByGroupId($project->getID()) as $tracker) {
            if ($tracker->isActive()) {
                $exported_trackers[$tracker->getXMLId()] = $tracker;

                $xml_tracker = $this->exportTracker($xml_trackers, $tracker, $xml_field_mapping);
                $this->artifact_xml_export->export($tracker, $xml_tracker, $user, $archive, $xml_field_mapping);
            }
        }

        $event = new TrackerEventExportFullXML(
            $user,
            $xml_content,
            $project,
            $exported_trackers,
            $archive
        );
        $this->event_manager->processEvent($event);

        $this->exportTriggers($xml_trackers, $xml_field_mapping, $exported_trackers);
        $this->validateTrackerExport($xml_trackers);
    }

    private function addUsedTypes(SimpleXMLElement $xml, Project $project)
    {
        $natures    = $xml->addChild('natures');
        $used_types = $this->nature_presenter_factory->getAllTypesEditableInProject($project);

        foreach ($used_types as $type) {
            $this->addTypeChild($natures, $type, $project->getID());
        }
    }

    private function addTypeChild(SimpleXMLElement $natures, TypePresenter $type, $project_id)
    {
        $cdata      = new XML_SimpleXMLCDATAFactory();
        $type_child = $cdata->insert($natures, 'nature', $type->shortname);
        if ($this->artifact_links_usage_dao->isTypeDisabledInProject($project_id, $type->shortname)) {
            $type_child->addAttribute('is_used', 0);
        }
    }

    public function exportToXml(
        Project $project,
        SimpleXMLElement $xml_content,
        PFUser $user,
    ) {
        $exported_trackers = [];
        $xml_field_mapping = [];

        $xml_trackers = $xml_content->addChild('trackers');

        $this->addUsedTypes($xml_content, $project);
        foreach ($this->tracker_factory->getTrackersByGroupId($project->getID()) as $tracker) {
            if ($tracker->isActive()) {
                $exported_trackers[$tracker->getXMLId()] = $tracker;
                $this->exportTracker($xml_trackers, $tracker, $xml_field_mapping);
            }
        }

        $this->event_manager->dispatch(
            new TrackerEventExportStructureXML(
                $user,
                $xml_content,
                $project,
                $exported_trackers,
            )
        );

        $this->exportTriggers($xml_trackers, $xml_field_mapping, $exported_trackers);
        $this->validateTrackerExport($xml_trackers);
    }

    private function exportTracker(SimpleXMLElement $xml_trackers, Tracker $tracker, &$xml_field_mapping)
    {
        $child = null;

        if ($tracker->isActive()) {
            $child = $xml_trackers->addChild('tracker');
            $tracker->exportToXML($child, $xml_field_mapping);

            if (isset($tracker) && $tracker->isProjectAllowedToUseType()) {
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
            $partial_element = new SimpleXMLElement((string) $xml_trackers->asXML());
            foreach ($partial_element->tracker as $xml_tracker) {
                $this->external_field_extractor->extractExternalFieldsFromTracker($xml_tracker);
            }
            $this->rng_validator->validate($partial_element, __DIR__ . '/../resources/trackers.rng');
            return $xml_trackers;
        } catch (XML_ParseException $exception) {
            foreach ($exception->getErrors() as $parse_error) {
                $this->logger->error($parse_error . PHP_EOL);
            }
        }
    }

    public function exportSingleTrackerToXml(
        SimpleXMLElement $xml_content,
        $tracker_id,
        PFUser $user,
        Tuleap\Project\XML\Export\ArchiveInterface $archive,
    ) {
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);

        if ($tracker !== null && $tracker->isActive()) {
            $xml_content = $xml_content->addChild('trackers');
            $this->exportTrackerAndArtifacts($user, $archive, $xml_content, $tracker);
        }

        return $xml_content;
    }

    public function exportSingleTrackerBunchOfArtifactsToXml(
        $tracker_id,
        PFUser $user,
        Tuleap\Project\XML\Export\ArchiveInterface $archive,
        array $artifacts,
    ): SimpleXMLElement {
        $tracker     = $this->tracker_factory->getTrackerById($tracker_id);
        $xml_content = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                                         <trackers />'
        );

        $xml_field_mapping = [];
        if ($tracker !== null && $tracker->isActive()) {
            $this->exportTracker($xml_content, $tracker, $xml_field_mapping);
        }

        $this->artifact_xml_export->exportBunchOfArtifactsForArchive($artifacts, $xml_content, $user, $archive, $xml_field_mapping);

        return $xml_content;
    }

    private function validateExport(SimpleXMLElement $xml_trackers): void
    {
        $partial_element = new SimpleXMLElement((string) $xml_trackers->asXML());

        foreach ($partial_element->tracker as $xml_tracker) {
            $this->external_field_extractor->extractExternalFieldsFromTracker($xml_tracker);
        }

        $this->rng_validator->validate($partial_element, __DIR__ . '/../resources/trackers.rng');
    }

    private function exportMapping(SimpleXMLElement $tracker_xml, Tracker $tracker): array
    {
        $xml_field_mapping = [];

        $tracker->exportToXMLInProjectExportContext($tracker_xml, $this->user_xml_exporter, $xml_field_mapping);

        return $xml_field_mapping;
    }

    private function exportTrackerAndArtifacts(PFUser $user, ArchiveInterface $archive, SimpleXMLElement $xml_content, Tracker $tracker): void
    {
        $tracker_xml_node = $xml_content->addChild('tracker');
        $mapping          = $this->exportMapping($tracker_xml_node, $tracker);
        $this->artifact_xml_export->export($tracker, $tracker_xml_node, $user, $archive, $mapping);
        $this->validateExport($xml_content);
    }
}
