<?php
/**
* Copyright Enalean (c) 2013 - 2018. All rights reserved.
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
use Tuleap\Tracker\Semantic\IRetrieveSemanticFromXML;

class Cardwall_Semantic_CardFieldsFactory implements IRetrieveSemanticFromXML
{
    /**
     * Creates a Cardwall_Semantic_CardFields Object
     *
     * @param SimpleXMLElement $xml          containing the structure of the imported semantic initial effort
     * @param array            &$xml_mapping containig the newly created formElements idexed by their XML IDs
     * @param Tracker          $tracker      to which the semantic is attached
     *
     * @return Cardwall_Semantic_CardFields The semantic object
     */
    public function getInstanceFromXML($xml, &$xml_mapping, $tracker)
    {
        $extractor        = new CardFieldXmlExtractor();
        $fields           = $extractor->extractFieldFromXml($xml, $xml_mapping);
        $background_color = $extractor->extractBackgroundColorFromXml($xml, $xml_mapping);

        $semantic = Cardwall_Semantic_CardFields::load($tracker);
        $semantic->setFields($fields);
        $semantic->setBackgroundColorField($background_color);

        return $semantic;
    }
}
