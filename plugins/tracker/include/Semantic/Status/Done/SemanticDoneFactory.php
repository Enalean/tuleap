<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

namespace Tuleap\Tracker\Semantic\Status\Done;

use SimpleXMLElement;
use Tuleap\Tracker\Semantic\Status\RetrieveSemanticStatus;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Semantic\TrackerSemantic;
use Tuleap\Tracker\Semantic\XML\IBuildSemanticFromXML;
use Tuleap\Tracker\Tracker;

class SemanticDoneFactory implements IBuildSemanticFromXML
{
    public function __construct(
        private readonly SemanticDoneDao $dao,
        private readonly SemanticDoneValueChecker $value_checker,
        private readonly RetrieveSemanticStatus $semantic_status_retriever,
    ) {
    }

    /**
     * @return SemanticDone
     */
    public function getInstanceByTracker(Tracker $tracker)
    {
        return SemanticDone::load($tracker);
    }

    #[\Override]
    public function getInstanceFromXML(
        SimpleXMLElement $current_semantic_xml,
        SimpleXMLElement $all_semantics_xml,
        array $xml_mapping,
        Tracker $tracker,
        array $tracker_mapping,
    ): ?TrackerSemantic {
        $semantic_status = $this->semantic_status_retriever->fromTracker($tracker);
        $done_values     = $this->getDoneValues($current_semantic_xml, $all_semantics_xml, $xml_mapping);

        return new SemanticDone($tracker, $semantic_status, $this->dao, $this->value_checker, $done_values);
    }

    /**
     * @return array
     */
    private function getDoneValues(SimpleXMLElement $xml, SimpleXMLElement $full_semantic_xml, array $xml_mapping)
    {
        $done_values         = [];
        $xml_semantic_status = $this->getXMLSemanticStatusFromAllSemanticsXML($full_semantic_xml);

        if (! $xml_semantic_status) {
            return $done_values;
        }

        foreach ($xml->closed_values->closed_value as $xml_closed_value) {
            $ref = (string) $xml_closed_value['REF'];

            if (! isset($xml_mapping[$ref])) {
                continue;
            }
            $value = $xml_mapping[$ref];

            if ($value && $this->value_checker->isValueAPossibleDoneValueInXMLImport($value, $xml_semantic_status)) {
                $done_values[] = $value;
            }
        }

        return $done_values;
    }

    private function getXMLSemanticStatusFromAllSemanticsXML(SimpleXMLElement $full_semantic_xml): ?SimpleXMLElement
    {
        foreach ($full_semantic_xml->semantic as $xml_semantic) {
            if ((string) $xml_semantic['type'] === TrackerSemanticStatus::NAME) {
                return $xml_semantic;
            }
        }

        return null;
    }
}
