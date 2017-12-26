<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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
    public function getInstanceFromXML(SimpleXMLElement $xml, array &$xmlMapping, Tracker $tracker)
    {
        $semantic_status = Tracker_Semantic_Status::load($tracker);
        $done_values     = $this->getDoneValues($xml, $xmlMapping);

        return new SemanticDone($tracker, $semantic_status, $this->dao, $this->value_checker, $done_values);
    }

    /**
     * @return array
     */
    private function getDoneValues(SimpleXMLElement $xml, array $xmlMapping)
    {
        $done_values = array();
        foreach ($xml->closed_values->closed_value as $xml_closed_value) {
            $ref   = (string) $xml_closed_value['REF'];
            $value = $xmlMapping[$ref];

            if ($value && ! $value->isHidden()) {
                $done_values[] = $value;
            }
        }

        return $done_values;
    }
}
