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

use Logger;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tuleap\Project\XML\Import\ImportConfig;

include __DIR__ . '/bootstrap.php';

class ReferencesImporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CompatibilityDao
     */
    private $dao;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Logger
     */
    private $logger;

    /**
     * @var ReferencesImporter
     */
    private $importer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao        = \Mockery::spy(CompatibilityDao::class);
        $this->logger     = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->importer   = new ReferencesImporter($this->dao, $this->logger);
    }

    public function testItShouldAddMediaWikiLinks(): void
    {
        $xml = <<<XML
            <references>
                <reference source="wiki76532" target="HomePage" />
            </references>
XML;
        $simple_xml = new SimpleXMLElement($xml);

        $this->dao->shouldReceive('getRef')->andReturns(\TestHelper::arrayToDar([]));

        $project = \Mockery::spy(\Project::class);

        $this->dao->shouldReceive('insertRef')->with($project, "wiki76532", "HomePage")->once();

        $this->importer->importCompatRefXML(new ImportConfig(), $project, $simple_xml, array());
    }

    public function testItShouldNotAddUnknownReferences(): void
    {
        $xml = <<<XML
            <references>
                <reference source="stuff1234" target="1"/>
            </references>
XML;
        $simple_xml = new SimpleXMLElement($xml);

        $this->dao->shouldReceive('getRef')->andReturns(\TestHelper::arrayToDar([]));

        $this->dao->shouldReceive('insertRef')->never();

        $this->importer->importCompatRefXML(new ImportConfig(), \Mockery::spy(\Project::class), $simple_xml, array());
    }
}
