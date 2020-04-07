<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\ReferenceAliasCore;

use DataAccessResult;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Project\XML\Import\ImportConfig;

include 'bootstrap.php';

class ReferencesImporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function setUp(): void
    {
        parent::setUp();
        $this->dao      = \Mockery::spy(\Tuleap\ReferenceAliasCore\Dao::class);
        $this->logger   = \Mockery::spy(\Psr\Log\LoggerInterface::class);
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

        $dar = \Mockery::spy(DataAccessResult::class);
        $dar->shouldReceive('getRow')->andReturns([]);

        $this->dao->shouldReceive('getRef')->andReturn($dar);
        $this->dao->shouldReceive('insertRef')->times(2);
        $this->importer->importCompatRefXML(new ImportConfig(), \Mockery::spy(\Project::class), $xml, $created_references);
    }

    public function testItShouldAddRelLinks()
    {
        $xml = <<<XML
            <references>
                <reference source="rel4567" target="3"/>
                <reference source="rel34"   target="4"/>
            </references>
XML;
        $xml = new \SimpleXMLElement($xml);
        $created_references = array('release' => array('3' => '6778', '4' => '6779'));

        $dar = \Mockery::spy(DataAccessResult::class);
        $dar->shouldReceive('getRow')->andReturns([]);

        $this->dao->shouldReceive('getRef')->andReturn($dar);
        $this->dao->shouldReceive('insertRef')->times(2);
        $this->importer->importCompatRefXML(new ImportConfig(), \Mockery::spy(\Project::class), $xml, $created_references);
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

        $dar = \Mockery::spy(DataAccessResult::class);
        $dar->shouldReceive('getRow')->andReturns([]);

        $this->dao->shouldReceive('getRef')->andReturn($dar);
        $this->dao->shouldReceive('insertRef')->never();
        $this->importer->importCompatRefXML(new ImportConfig(), \Mockery::spy(\Project::class), $xml, $created_references);
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

        $dar = \Mockery::spy(DataAccessResult::class);
        $dar->shouldReceive('getRow')->andReturns([]);

        $this->dao->shouldReceive('getRef')->andReturn($dar);
        $this->dao->shouldReceive('insertRef')->never();
        $this->importer->importCompatRefXML(new ImportConfig(), \Mockery::spy(\Project::class), $xml, $created_references);
    }
}
