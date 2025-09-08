<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress;

use SimpleXMLElement;
use Tuleap\Tracker\Semantic\TrackerSemantic;
use Tuleap\Tracker\Semantic\XML\IBuildSemanticFromXML;
use Tuleap\Tracker\Tracker;

class SemanticProgressFromXMLBuilder implements IBuildSemanticFromXML
{
    /**
     * @var SemanticProgressDao
     */
    private $dao;

    public function __construct(SemanticProgressDao $dao)
    {
        $this->dao = $dao;
    }

    #[\Override]
    public function getInstanceFromXML(
        SimpleXMLElement $current_semantic_xml,
        SimpleXMLElement $all_semantics_xml,
        array $xml_mapping,
        Tracker $tracker,
        array $tracker_mapping,
    ): ?TrackerSemantic {
        if ($this->isEffortBased($current_semantic_xml)) {
            return $this->buildEffortBasedSemanticProgress($current_semantic_xml, $xml_mapping, $tracker);
        }

        if ($this->isLinksCountBased($current_semantic_xml)) {
            return $this->buildLinksCountBasedSemanticProgress($current_semantic_xml, $tracker);
        }

        return null;
    }

    private function buildEffortBasedSemanticProgress(SimpleXMLElement $xml, array $xml_mapping, Tracker $tracker): ?SemanticProgress
    {
        $xml_total_effort_field = $xml->total_effort_field;
        $total_effort_field     = $this->getFieldTargetedInXMLFieldAttributes(
            $xml_mapping,
            $xml_total_effort_field->attributes()
        );

        $xml_remaining_effort_field = $xml->remaining_effort_field;
        $remaining_effort_field     = $this->getFieldTargetedInXMLFieldAttributes(
            $xml_mapping,
            $xml_remaining_effort_field->attributes()
        );

        if ($total_effort_field === null || $remaining_effort_field === null) {
            return null;
        }

        return new SemanticProgress(
            $tracker,
            new MethodBasedOnEffort(
                $this->dao,
                $total_effort_field,
                $remaining_effort_field
            )
        );
    }

    private function isEffortBased(SimpleXMLElement $xml): bool
    {
        return isset($xml->total_effort_field) &&
            isset($xml->remaining_effort_field) &&
            ! isset($xml->artifact_link_type);
    }

    private function getFieldTargetedInXMLFieldAttributes(array $xml_mapping, ?SimpleXMLElement $xml_field_attributes): ?\Tuleap\Tracker\FormElement\Field\NumericField
    {
        if ($xml_field_attributes === null || ! isset($xml_field_attributes['REF'])) {
            return null;
        }

        if (! isset($xml_mapping[(string) $xml_field_attributes['REF']])) {
            return null;
        }

        return $xml_mapping[(string) $xml_field_attributes['REF']];
    }

    private function isLinksCountBased(SimpleXMLElement $xml): bool
    {
        return isset($xml->artifact_link_type) &&
            ! isset($xml->total_effort_field) &&
            ! isset($xml->remaining_effort_field);
    }

    private function buildLinksCountBasedSemanticProgress(SimpleXMLElement $xml, Tracker $tracker): ?SemanticProgress
    {
        $link_type = $xml->artifact_link_type->attributes();
        if ($link_type === null || ! isset($link_type['shortname'])) {
            return null;
        }

        return new SemanticProgress(
            $tracker,
            new MethodBasedOnLinksCount(
                $this->dao,
                (string) $link_type['shortname']
            )
        );
    }
}
