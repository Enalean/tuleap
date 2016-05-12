<?php
/**
 * Copyright (c) Sogilis, 2016. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
namespace Tuleap\ReferenceAliasTracker;

include 'bootstrap.php';

class ReferencesImporterTest extends \TuleapTestCase {

    public function setUp() {
        $this->dao      = mock('Tuleap\ReferenceAliasTracker\Dao');
        $this->logger   = mock('Logger');
        $this->importer = new ReferencesImporter($this->dao, $this->logger);
    }

    public function testItShouldAddArtifactAndTrackerLinks() {
        $xml = <<<XML
            <references>
                <reference source="artf1234" target="1"/>
                <reference source="tracker12" target="T2"/>
            </references>
XML;
        $simple_xml         = new \SimpleXMLElement($xml);
        $created_references = array('tracker' => array('T2' => '12'), 'artifact' => array('1' => '2'));

        stub($this->dao)->getRef()->returns(mock('DataAccessResult'));

        expect($this->dao)->insertRef()->count(2);

        $this->importer->importCompatRefXML(mock('Project'), $simple_xml, $created_references);
    }

    public function testItShouldNotAddIfTargetIsUnknown() {
        $xml = <<<XML
            <references>
                <reference source="artf1234" target="1"/>
                <reference source="tracker12" target="T2"/>
            </references>
XML;
        $simple_xml         = new \SimpleXMLElement($xml);
        $created_references = array();

        stub($this->dao)->getRef()->returns(mock('DataAccessResult'));

        expect($this->dao)->insertRef()->never();

        $this->importer->importCompatRefXML(mock('Project'), $simple_xml, $created_references);
    }

    public function testItShouldNotAddUnknownReferences() {
        $xml = <<<XML
            <references>
                <reference source="stuff1234" target="1"/>
            </references>
XML;
        $simple_xml         = new \SimpleXMLElement($xml);
        $created_references = array('package' => array('1' => '1337'));

        stub($this->dao)->getRef()->returns(mock('DataAccessResult'));

        expect($this->dao)->insertRef()->never();

        $this->importer->importCompatRefXML(mock('Project'), $simple_xml, $created_references);
    }
}
