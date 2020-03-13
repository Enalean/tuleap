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
use Tuleap\Tracker\Semantic\IBuildSemanticFromXML;
use Tracker_Semantic;

class SemanticTimeframeFromXMLBuilder implements IBuildSemanticFromXML
{
    public function getInstanceFromXML(SimpleXMLElement $xml, array $xml_mapping, Tracker $tracker): ?Tracker_Semantic
    {
        $xml_start_date_field = $xml->start_date_field;
        $xml_start_date_field_attributes = $xml_start_date_field->attributes();

        if (! isset($xml_mapping[(string) $xml_start_date_field_attributes['REF']])) {
            return null;
        }
        $start_date_field = $xml_mapping[(string) $xml_start_date_field_attributes['REF']];

        if (isset($xml->duration_field)) {
            $xml_duration_field = $xml->duration_field;
            $xml_duration_field_attributes = $xml_duration_field->attributes();
            $duration_field = $xml_mapping[(string) $xml_duration_field_attributes['REF']];

            return new SemanticTimeframe($tracker, $start_date_field, $duration_field, null);
        }

        $xml_end_date_field = $xml->end_date_field;
        $xml_end_date_field_attributes = $xml_end_date_field->attributes();

        if (! isset($xml_mapping[(string) $xml_end_date_field_attributes['REF']])) {
            return null;
        }
        $end_date_field = $xml_mapping[(string) $xml_end_date_field_attributes['REF']];

        return new SemanticTimeframe($tracker, $start_date_field, null, $end_date_field);
    }
}
