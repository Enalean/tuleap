<?php
/**
 * Copyright (c) Sogilis, 2016. All Rights Reserved.
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
namespace TeamforgeCompat;
include 'bootstrap.php';

class TeamforgeReferencesImporterTest extends \TuleapTestCase
{

    public function setUp()
    {
        $this->dao      = mock('TeamforgeCompat\TeamforgeCompatDao');
        $this->logger   = mock('Logger');
        $this->importer = new ReferencesImporter($this->dao, $this->logger);
    }

    public function testItShouldAddPkgLinks()
    {
        $xml = <<<XML
            <references>
                <reference source="pkg1234" target="1"/>
                <reference source="pkg12"   target="2"/>
            </references>
XML;
        $xml = new \SimpleXMLElement($xml);
        $created_references = array('package' => array('1' => '1337', '2' => '42'));

        stub($this->dao)->getRef()->returns(mock('DataAccessResult'));
        expect($this->dao)->insertRef()->count(2);
        $this->importer->importCompatRefXML(mock('Project'), $xml, $created_references);
    }

    public function testItShouldNotAddIfTargetIsUnknown()
    {
        $xml = <<<XML
            <references>
                <reference source="pkg1234" target="456"/>
            </references>
XML;
        $xml = new \SimpleXMLElement($xml);
        $created_references = array('package' => array());

        stub($this->dao)->getRef()->returns(mock('DataAccessResult'));
        expect($this->dao)->insertRef()->never();
        $this->importer->importCompatRefXML(mock('Project'), $xml, $created_references);
    }

    public function testItShouldNotAddUnknownReferences()
    {
        $xml = <<<XML
            <references>
                <reference source="stuff1234" target="1"/>
            </references>
XML;
        $xml = new \SimpleXMLElement($xml);
        $created_references = array('package' => array('1' => '1337'));

        stub($this->dao)->getRef()->returns(mock('DataAccessResult'));
        expect($this->dao)->insertRef()->never();
        $this->importer->importCompatRefXML(mock('Project'), $xml, $created_references);
    }
}
