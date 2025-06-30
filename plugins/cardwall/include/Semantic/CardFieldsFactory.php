<?php
/**
* Copyright Enalean (c) 2013 - Present. All rights reserved.
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

use Tuleap\Cardwall\Semantic\CardFieldXmlExtractor;
use Tuleap\Tracker\Semantic\TrackerSemantic;
use Tuleap\Tracker\Semantic\XML\IBuildSemanticFromXML;
use Tuleap\Tracker\Tracker;

class Cardwall_Semantic_CardFieldsFactory implements IBuildSemanticFromXML  // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function getInstanceFromXML(
        SimpleXMLElement $current_semantic_xml,
        SimpleXMLElement $all_semantics_xml,
        array $xml_mapping,
        Tracker $tracker,
        array $tracker_mapping,
    ): TrackerSemantic {
        $extractor        = new CardFieldXmlExtractor();
        $fields           = $extractor->extractFieldFromXml($current_semantic_xml, $xml_mapping);
        $background_color = $extractor->extractBackgroundColorFromXml($current_semantic_xml, $xml_mapping);

        $semantic = Cardwall_Semantic_CardFields::load($tracker);
        $semantic->setFields($fields);
        $semantic->setBackgroundColorField($background_color);

        return $semantic;
    }
}
