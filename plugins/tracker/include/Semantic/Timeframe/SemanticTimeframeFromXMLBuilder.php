<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use SimpleXMLElement;
use TrackerFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinksRetriever;
use Tuleap\Tracker\Semantic\XML\IBuildSemanticFromXML;
use Tuleap\Tracker\Tracker;

class SemanticTimeframeFromXMLBuilder implements IBuildSemanticFromXML
{
    private ArtifactLinkFieldValueDao $artifact_link_field_value_dao;
    private TrackerFactory $tracker_factory;
    private SemanticTimeframeBuilder $semantic_timeframe_builder;

    public function __construct(
        ArtifactLinkFieldValueDao $artifact_link_field_value_dao,
        TrackerFactory $tracker_factory,
        SemanticTimeframeBuilder $semantic_timeframe_builder,
    ) {
        $this->artifact_link_field_value_dao = $artifact_link_field_value_dao;
        $this->tracker_factory               = $tracker_factory;
        $this->semantic_timeframe_builder    = $semantic_timeframe_builder;
    }

    public function getInstanceFromXML(
        SimpleXMLElement $current_semantic_xml,
        SimpleXMLElement $all_semantics_xml,
        array $xml_mapping,
        Tracker $tracker,
        array $tracker_mapping,
    ): ?SemanticTimeframe {
        if (! isset($current_semantic_xml->start_date_field)) {
            return $this->getSemanticTimeframeInheritedFromAnotherTrackerInstance($current_semantic_xml, $tracker, $tracker_mapping);
        }

        $xml_start_date_field            = $current_semantic_xml->start_date_field;
        $xml_start_date_field_attributes = $xml_start_date_field->attributes();

        if (! isset($xml_mapping[(string) $xml_start_date_field_attributes['REF']])) {
            return null;
        }

        $start_date_field = $xml_mapping[(string) $xml_start_date_field_attributes['REF']];

        if (isset($current_semantic_xml->duration_field)) {
            $xml_duration_field            = $current_semantic_xml->duration_field;
            $xml_duration_field_attributes = $xml_duration_field->attributes();

            if (! isset($xml_mapping[(string) $xml_duration_field_attributes['REF']])) {
                return null;
            }

            $duration_field = $xml_mapping[(string) $xml_duration_field_attributes['REF']];

            return new SemanticTimeframe($tracker, new TimeframeWithDuration($start_date_field, $duration_field));
        }

        $xml_end_date_field            = $current_semantic_xml->end_date_field;
        $xml_end_date_field_attributes = $xml_end_date_field->attributes();

        if (! isset($xml_mapping[(string) $xml_end_date_field_attributes['REF']])) {
            return null;
        }
        $end_date_field = $xml_mapping[(string) $xml_end_date_field_attributes['REF']];

        return new SemanticTimeframe($tracker, new TimeframeWithEndDate($start_date_field, $end_date_field));
    }

    private function getSemanticTimeframeInheritedFromAnotherTrackerInstance(SimpleXMLElement $current_semantic_xml, Tracker $tracker, array $tracker_mapping): ?SemanticTimeframe
    {
        if (! isset($current_semantic_xml->inherited_from_tracker)) {
            return null;
        }

        $xml_implied_from_tracker        = $current_semantic_xml->inherited_from_tracker;
        $implied_from_tracker_attributes = $xml_implied_from_tracker->attributes();
        $implied_from_tracker_id         = (string) $implied_from_tracker_attributes['id'];

        if (count($tracker_mapping) === 0 || ! isset($tracker_mapping[$implied_from_tracker_id])) {
                return null;
        }

        $created_implied_from_tracker_id = $tracker_mapping[$implied_from_tracker_id];
        $implied_from_tracker            = $this->tracker_factory->getTrackerById((int) $created_implied_from_tracker_id);

        if ($implied_from_tracker === null) {
            return $this->semantic_timeframe_builder->buildTimeframeSemanticNotConfigured($tracker);
        }

        $implied_semantic = $this->semantic_timeframe_builder->getSemantic($implied_from_tracker);

        if ($implied_semantic->isTimeframeNotConfiguredNorImplied()) {
            return $this->semantic_timeframe_builder->buildTimeframeSemanticNotConfigured($tracker);
        }

        $links_retriever = new LinksRetriever(
            $this->artifact_link_field_value_dao,
            \Tracker_ArtifactFactory::instance()
        );

        return new SemanticTimeframe(
            $tracker,
            new TimeframeImpliedFromAnotherTracker(
                $tracker,
                $implied_semantic,
                $links_retriever
            )
        );
    }
}
