<?php
/**
 * Copyright (c) Enalean SAS, 2016 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ReferenceAliasMediawiki;

use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Test\Builders\ProjectTestBuilder;

include __DIR__ . '/bootstrap.php';

final class ReferencesImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var CompatibilityDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;
    private ReferencesImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao      = $this->createMock(CompatibilityDao::class);
        $this->importer = new ReferencesImporter($this->dao, new NullLogger());
    }

    public function testItShouldAddMediaWikiLinks(): void
    {
        $xml        = <<<XML
            <references>
                <reference source="wiki76532" target="HomePage" />
            </references>
XML;
        $simple_xml = new SimpleXMLElement($xml);

        $this->dao->method('getRef')->willReturn([]);
        $this->dao->expects(self::once())->method('insertRef')->with(101, "wiki76532", "HomePage");

        $this->importer->importCompatRefXML(new ImportConfig(), ProjectTestBuilder::aProject()->build(), $simple_xml, []);
    }

    public function testItShouldNotAddUnknownReferences(): void
    {
        $xml        = <<<XML
            <references>
                <reference source="stuff1234" target="1"/>
            </references>
XML;
        $simple_xml = new SimpleXMLElement($xml);

        $this->dao->method('getRef')->willReturn([]);
        $this->dao->expects(self::never())->method('insertRef');

        $this->importer->importCompatRefXML(new ImportConfig(), ProjectTestBuilder::aProject()->build(), $simple_xml, []);
    }
}
