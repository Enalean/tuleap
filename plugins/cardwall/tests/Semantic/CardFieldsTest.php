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

use Tuleap\Cardwall\Semantic\BackgroundColorFieldRetriever;
use Tuleap\Cardwall\Semantic\BackgroundColorFieldSaver;
use Tuleap\Cardwall\Semantic\BackgroundColorPresenterBuilder;
use Tuleap\Cardwall\Semantic\CardFieldsPresenterBuilder;
use Tuleap\Cardwall\Semantic\FieldUsedInSemanticObjectChecker;

require_once dirname(__FILE__) .'/../bootstrap.php';

class Cardwall_Semantic_CardFieldsTest extends TuleapTestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var XML_Security */
    protected $xml_security;

    public function setUp()
    {
        parent::setUp();

        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();
    }

    public function tearDown()
    {
        $this->xml_security->disableExternalLoadOfEntities();

        parent::tearDown();
    }

    public function itExportsInXMLFormat()
    {
        $tracker  = mock('Tracker');
        $field_1  = stub('Tracker_FormElement_Field_Text')->getId()->returns(102);
        $field_2  = stub('Tracker_FormElement_Field_Text')->getId()->returns(103);

        $checker         = Mockery::spy(FieldUsedInSemanticObjectChecker::class);
        $builder         = Mockery::spy(BackgroundColorPresenterBuilder::class);
        $saver           = Mockery::spy(BackgroundColorFieldSaver::class);
        $field_builder   = Mockery::spy(CardFieldsPresenterBuilder::class);
        $field_retriever = Mockery::spy(BackgroundColorFieldRetriever::class);

        $semantic = new Cardwall_Semantic_CardFields(
            $tracker,
            $checker,
            $builder,
            $saver,
            $field_builder,
            $field_retriever
        );
        $semantic->setFields(array($field_1, $field_2));

        $root = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_mapping = array('F13' => '102', 'F14' => '103');
        $semantic->exportToXML($root, $array_mapping);

        $xml = simplexml_load_file(dirname(__FILE__) . '/_fixtures/ImportCardwallSemanticCardFields.xml');
        $this->assertEqual((string)$xml['type'], (string)$root->semantic['type']);
        $this->assertEqual((string)$xml->field[0]['REF'], (string)$root->semantic->field[0]['REF']);
        $this->assertEqual((string)$xml->field[1]['REF'], (string)$root->semantic->field[1]['REF']);
    }
}
