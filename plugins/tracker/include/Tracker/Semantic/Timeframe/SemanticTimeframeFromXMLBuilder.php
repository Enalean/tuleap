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
use Tracker;
use Tuleap\Tracker\Semantic\IRetrieveSemanticFromXML;
use Tracker_Semantic;

class SemanticTimeframeFromXMLBuilder implements IRetrieveSemanticFromXML
{
    /**
     * Creates a Tracker_Semantic_Contributor Object
     *
     * @param SimpleXMLElement  $xml        containing the structure of the imported semantic contributor
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Tracker           $tracker    to which the semantic is attached
     *
     * @return Tracker_Semantic The semantic object
     */
    public function getInstanceFromXML($xml, &$xmlMapping, $tracker)
    {
        $xml_start_date_field = $xml->start_date_field;
        $xml_start_date_field_attributes = $xml_start_date_field->attributes();
        $start_date_field = $xmlMapping[(string)$xml_start_date_field_attributes['REF']];

        $xml_duration_field = $xml->duration_field;
        $xml_duration_field_attributes = $xml_duration_field->attributes();
        $duration_field = $xmlMapping[(string)$xml_duration_field_attributes['REF']];

        return new SemanticTimeframe($tracker, $start_date_field, $duration_field);
    }
}
