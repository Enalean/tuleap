<?php
/**
 * Copyright Enalean (c) 2017 - 2018. All rights reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use SimpleXMLElement;
use Tracker;
use Tracker_Semantic_Status;
use Tuleap\AgileDashboard\Semantic\Dao\SemanticDoneDao;

class SemanticDoneFactory
{
    /**
     * @var SemanticDoneDao
     */
    private $dao;

    /**
     * @var SemanticDoneValueChecker
     */
    private $value_checker;

    public function __construct(SemanticDoneDao $dao, SemanticDoneValueChecker $value_checker)
    {
        $this->dao           = $dao;
        $this->value_checker = $value_checker;
    }

    /**
     * @return SemanticDone
     */
    public function getInstanceByTracker(Tracker $tracker)
    {
        return SemanticDone::load($tracker);
    }

    /**
     * @return SemanticDone
     */
    public function getInstanceFromXML(
        SimpleXMLElement $xml,
        SimpleXMLElement $full_semantic_xml,
        array &$xmlMapping,
        Tracker $tracker
    ) {
        $semantic_status = Tracker_Semantic_Status::load($tracker);
        $done_values     = $this->getDoneValues($xml, $full_semantic_xml, $xmlMapping);

        return new SemanticDone($tracker, $semantic_status, $this->dao, $this->value_checker, $done_values);
    }

    /**
     * @return array
     */
    private function getDoneValues(SimpleXMLElement $xml, SimpleXMLElement $full_semantic_xml, array $xmlMapping)
    {
        $done_values         = array();
        $xml_semantic_status = $this->getSemanticDoneFromXML($full_semantic_xml);

        if (! $xml_semantic_status) {
            return $done_values;
        }

        foreach ($xml->closed_values->closed_value as $xml_closed_value) {
            $ref   = (string) $xml_closed_value['REF'];

            if (! isset($xmlMapping[$ref])) {
                continue;
            }
            $value = $xmlMapping[$ref];

            if ($value && $this->value_checker->isValueAPossibleDoneValueInXMLImport($value, $xml_semantic_status)) {
                $done_values[] = $value;
            }
        }

        return $done_values;
    }

    private function getSemanticDoneFromXML(SimpleXMLElement $full_semantic_xml)
    {
        foreach ($full_semantic_xml->semantic as $xml_semantic) {
            if ((string) $xml_semantic['type'] === Tracker_Semantic_Status::NAME) {
                return $xml_semantic;
            }
        }

        return null;
    }
}
