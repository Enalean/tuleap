<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Psr\Log\NullLogger;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Test\Builders\ProjectTestBuilder;

include 'bootstrap.php';

final class ReferencesImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ReferencesImporter $importer;
    /**
     * @var Dao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;

    public function setUp(): void
    {
        parent::setUp();
        $this->dao      = $this->createMock(\Tuleap\ReferenceAliasCore\Dao::class);
        $this->importer = new ReferencesImporter($this->dao, new NullLogger());
    }

    public function testItShouldAddPkgLinks(): void
    {
        $xml                = <<<XML
            <references>
                <reference source="pkg1234" target="1"/>
                <reference source="pkg12"   target="2"/>
            </references>
XML;
        $xml                = new \SimpleXMLElement($xml);
        $created_references = ['package' => ['1' => '1337', '2' => '42']];

        $this->dao->method('getRef')->willReturn([]);
        $this->dao->expects(self::exactly(2))->method('insertRef');

        $this->importer->importCompatRefXML(new ImportConfig(), ProjectTestBuilder::aProject()->build(), $xml, $created_references);
    }

    public function testItShouldAddRelLinks(): void
    {
        $xml                = <<<XML
            <references>
                <reference source="rel4567" target="3"/>
                <reference source="rel34"   target="4"/>
            </references>
XML;
        $xml                = new \SimpleXMLElement($xml);
        $created_references = ['release' => ['3' => '6778', '4' => '6779']];

        $this->dao->method('getRef')->willReturn([]);
        $this->dao->expects(self::exactly(2))->method('insertRef');

        $this->importer->importCompatRefXML(new ImportConfig(), ProjectTestBuilder::aProject()->build(), $xml, $created_references);
    }

    public function testItShouldNotAddIfTargetIsUnknown(): void
    {
        $xml                = <<<XML
            <references>
                <reference source="pkg1234" target="456"/>
            </references>
XML;
        $xml                = new \SimpleXMLElement($xml);
        $created_references = ['package' => []];

        $this->dao->method('getRef')->willReturn([]);
        $this->dao->expects(self::never())->method('insertRef');

        $this->importer->importCompatRefXML(new ImportConfig(), ProjectTestBuilder::aProject()->build(), $xml, $created_references);
    }

    public function testItShouldNotAddUnknownReferences(): void
    {
        $xml                = <<<XML
            <references>
                <reference source="stuff1234" target="1"/>
            </references>
XML;
        $xml                = new \SimpleXMLElement($xml);
        $created_references = ['package' => ['1' => '1337']];

        $this->dao->method('getRef')->willReturn([]);
        $this->dao->expects(self::never())->method('insertRef');

        $this->importer->importCompatRefXML(new ImportConfig(), ProjectTestBuilder::aProject()->build(), $xml, $created_references);
    }
}
