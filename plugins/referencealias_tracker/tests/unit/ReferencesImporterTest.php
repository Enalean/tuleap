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

namespace Tuleap\ReferenceAliasTracker;

use Psr\Log\NullLogger;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Test\Builders\ProjectTestBuilder;

include __DIR__ . '/bootstrap.php';

final class ReferencesImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ReferencesImporter $importer;
    private ImportConfig $configuration;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Dao
     */
    private $dao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao           = $this->createMock(\Tuleap\ReferenceAliasTracker\Dao::class);
        $this->importer      = new ReferencesImporter($this->dao, new NullLogger());
        $this->configuration = new ImportConfig();
    }

    public function testItShouldAddArtifactAndTrackerLinks(): void
    {
        $xml                = <<<XML
            <references>
                <reference source="artf1234" target="1"/>
                <reference source="plan678" target="5"/>
                <reference source="tracker12" target="T2"/>
            </references>
XML;
        $simple_xml         = new \SimpleXMLElement($xml);
        $created_references = ['tracker' => ['T2' => '12'], 'artifact' => ['1' => '2', '5' => '6']];

        $this->dao->method('getRef')->willReturn([]);
        $this->dao->expects(self::exactly(3))->method('insertRef');

        $this->importer->importCompatRefXML($this->configuration, ProjectTestBuilder::aProject()->build(), $simple_xml, $created_references);
    }

    public function testItShouldNotAddIfTargetIsUnknown(): void
    {
        $xml                = <<<XML
            <references>
                <reference source="artf1234" target="1"/>
                <reference source="tracker12" target="T2"/>
            </references>
XML;
        $simple_xml         = new \SimpleXMLElement($xml);
        $created_references = [];

        $this->dao->method('getRef')->willReturn([]);
        $this->dao->expects(self::never())->method('insertRef');

        $this->importer->importCompatRefXML($this->configuration, ProjectTestBuilder::aProject()->build(), $simple_xml, $created_references);
    }

    public function testItShouldNotAddUnknownReferences(): void
    {
        $xml                = <<<XML
            <references>
                <reference source="stuff1234" target="1"/>
            </references>
XML;
        $simple_xml         = new \SimpleXMLElement($xml);
        $created_references = ['package' => ['1' => '1337']];

        $this->dao->method('getRef')->willReturn([]);
        $this->dao->expects(self::never())->method('insertRef');

        $this->importer->importCompatRefXML($this->configuration, ProjectTestBuilder::aProject()->build(), $simple_xml, $created_references);
    }
}
