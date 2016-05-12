<?php
/**
 * Copyright (c) Enalean SAS, 2016. All Rights Reserved.
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
namespace Tuleap\ReferenceAliasGit;

include 'bootstrap.php';

use GitRepository;

class ReferencesImporterTest extends \TuleapTestCase
{

    public function setUp()
    {
        $this->dao        = mock('Tuleap\ReferenceAliasGit\Dao');
        $this->logger     = mock('Logger');
        $this->importer   = new ReferencesImporter($this->dao, $this->logger);
        $this->repository = mock('GitRepository');

        stub($this->repository)->getId()->returns(123);
        stub($this->repository)->getProjectId()->returns(101);
    }

    public function testItShouldAddGitLinks()
    {
        $xml = <<<XML
            <references>
                <reference source="cmmt12" target="le_sha1"/>
            </references>
XML;
        $simple_xml = new \SimpleXMLElement($xml);

        stub($this->dao)->getRef()->returns(mock('DataAccessResult'));

        expect($this->dao)->insertRef('cmmt12', 123, 'le_sha1')->once();

        $this->importer->importCompatRefXML(mock('Project'), $simple_xml, $this->repository);
    }

    public function testItShouldNotAddUnknownReferences()
    {
        $xml = <<<XML
            <references>
                <reference source="stuff1234" target="whatever"/>
            </references>
XML;
        $simple_xml = new \SimpleXMLElement($xml);

        stub($this->dao)->getRef()->returns(mock('DataAccessResult'));

        expect($this->dao)->insertRef()->never();

        $this->importer->importCompatRefXML(mock('Project'), $simple_xml, $this->repository);
    }
}
