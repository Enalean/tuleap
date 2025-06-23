<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Tracker\XML\Importer;

use Project;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use TrackerFactory;
use TrackerFromXmlImportCannotBeCreatedException;
use trackerPlugin;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\TrackerXMLFieldMappingFromExistingTracker;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;

final readonly class TrackerFromXmlInstantiator implements InstantiateTrackerFromXml
{
    public function __construct(
        private TrackerFactory $tracker_factory,
        private Tracker_FormElementFactory $formelement_factory,
        private CreateFromXml $from_xml_creator,
        private TrackerXmlImportFeedbackCollector $feedback_collector,
        private \Psr\Log\LoggerInterface $logger,
    ) {
    }

    public function instantiateTrackerFromXml(
        Project $project,
        SimpleXMLElement $xml_tracker,
        ImportConfig $configuration,
        array $created_trackers_mapping,
        TrackerXMLFieldMappingFromExistingTracker $existing_tracker_field_mapping,
        array &$xml_fields_mapping,
        array &$reports_xml_mapping,
        array &$renderers_xml_mapping,
    ): Tracker {
        $tracker_existing = $this->getTrackerToReUse(
            $project,
            $xml_tracker,
            $configuration,
            $existing_tracker_field_mapping,
            $xml_fields_mapping,
        );
        if ($tracker_existing !== null) {
            return $tracker_existing;
        }

        try {
            return $this->from_xml_creator->createFromXML(
                $xml_tracker,
                $project,
                (string) $xml_tracker->name,
                (string) $xml_tracker->description,
                (string) $xml_tracker->item_name,
                (string) $xml_tracker->color,
                $created_trackers_mapping,
                $xml_fields_mapping,
                $reports_xml_mapping,
                $renderers_xml_mapping,
            );
        } catch (\Tuleap\Tracker\TrackerIsInvalidException $exception) {
            $this->feedback_collector->addErrors($exception->getTranslatedMessage());
            $this->feedback_collector->displayErrors($this->logger);
            throw new TrackerFromXmlImportCannotBeCreatedException((string) $xml_tracker->name);
        }
    }

    /**
     * @return null|Tracker
     */
    private function getTrackerToReUse(
        Project $project,
        SimpleXMLElement $xml_tracker,
        ImportConfig $configuration,
        TrackerXMLFieldMappingFromExistingTracker $existing_tracker_field_mapping,
        array &$xml_fields_mapping,
    ) {
        foreach ($configuration->getExtraConfiguration() as $extra_configuration) {
            if ($extra_configuration->getServiceName() !== trackerPlugin::SERVICE_SHORTNAME) {
                continue;
            }

            $tracker_shortname = (string) $xml_tracker->item_name;

            if (in_array($tracker_shortname, $extra_configuration->getValue())) {
                $tracker_existing = $this->tracker_factory->getTrackerByShortnameAndProjectId(
                    $tracker_shortname,
                    (int) $project->getID()
                );

                if ($tracker_existing) {
                    $this->fillFieldMappingFromExistingTracker(
                        $tracker_existing,
                        $xml_tracker,
                        $existing_tracker_field_mapping,
                        $xml_fields_mapping,
                    );

                    return $tracker_existing;
                }
            }
        }

        return null;
    }

    private function fillFieldMappingFromExistingTracker(
        Tracker $tracker,
        SimpleXMLElement $xml_tracker,
        TrackerXMLFieldMappingFromExistingTracker $existing_tracker_field_mapping,
        array &$xml_fields_mapping,
    ): void {
        $form_elements_existing = $this->formelement_factory->getFields($tracker);
        $xml_fields_mapping     = $existing_tracker_field_mapping->getXmlFieldsMapping(
            $xml_tracker,
            $form_elements_existing
        );
    }
}
