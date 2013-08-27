<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
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

require_once dirname(__FILE__) .'/../bootstrap.php';

class Cardwall_Semantic_CardFieldsFactoryTest extends TuleapTestCase {

    public function itImportsACardFieldsSemanticFromXMLFormat() {
        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportCardwallSemanticCardFields.xml');

        $tracker = mock('Tracker');

        $mapping = array(
            'F13' => 102,
            'F14' => 103
        );
        $factory = new Cardwall_Semantic_CardFieldsFactory();
        $semantic = $factory->getInstanceFromXML($xml, $mapping, $tracker);

        $fields = $semantic->getFields();
        $this->assertTrue(in_array(102, $fields));
        $this->assertTrue(in_array(103, $fields));
    }
}
