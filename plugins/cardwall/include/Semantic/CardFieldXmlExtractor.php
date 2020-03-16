<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Cardwall\Semantic;

use SimpleXMLElement;

class CardFieldXmlExtractor
{
    public function extractFieldFromXml(SimpleXMLElement $xml, array $xml_mapping)
    {
        $fields = [];
        foreach ($xml->field as $field) {
            $att      = $field->attributes();
            if (! isset($xml_mapping[(string) $att['REF']])) {
                continue;
            }
            $fields[] = $xml_mapping[(string) $att['REF']];
        }
        return $fields;
    }

    public function extractBackgroundColorFromXml(SimpleXMLElement $xml, array $xml_mapping)
    {
        $background_color_field = $xml->{ 'background-color' };
        if (! $background_color_field) {
            return null;
        }
        $att = $background_color_field->attributes();
        if (! isset($xml_mapping[(string) $att['REF']])) {
            return null;
        }
        return $xml_mapping[(string) $att['REF']];
    }
}
