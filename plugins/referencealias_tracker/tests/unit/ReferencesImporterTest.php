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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Project\XML\Import\ImportConfig;

include __DIR__ . '/bootstrap.php';

final class ReferencesImporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Dao
     */
    private $dao;

    /**
     * @var \Logger|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $logger;

    /**
     * @var ReferencesImporter
     */
    private $importer;

    /**
     * @var ImportConfig
     */
    private $configuration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao           = \Mockery::spy(\Tuleap\ReferenceAliasTracker\Dao::class);
        $this->logger        = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->importer      = new ReferencesImporter($this->dao, $this->logger);
        $this->configuration = new ImportConfig();
    }

    public function testItShouldAddArtifactAndTrackerLinks(): void
    {
        $xml = <<<XML
            <references>
                <reference source="artf1234" target="1"/>
                <reference source="plan678" target="5"/>
                <reference source="tracker12" target="T2"/>
            </references>
XML;
        $simple_xml         = new \SimpleXMLElement($xml);
        $created_references = array('tracker' => array('T2' => '12'), 'artifact' => array('1' => '2', '5' => '6'));

        $this->dao->shouldReceive('getRef')->andReturns(\TestHelper::arrayToDar([]));

        $this->dao->shouldReceive('insertRef')->times(3);

        $this->importer->importCompatRefXML($this->configuration, \Mockery::spy(\Project::class), $simple_xml, $created_references);
    }

    public function testItShouldNotAddIfTargetIsUnknown(): void
    {
        $xml = <<<XML
            <references>
                <reference source="artf1234" target="1"/>
                <reference source="tracker12" target="T2"/>
            </references>
XML;
        $simple_xml         = new \SimpleXMLElement($xml);
        $created_references = array();

        $this->dao->shouldReceive('getRef')->andReturns(\TestHelper::arrayToDar([]));

        $this->dao->shouldReceive('insertRef')->never();

        $this->importer->importCompatRefXML($this->configuration, \Mockery::spy(\Project::class), $simple_xml, $created_references);
    }

    public function testItShouldNotAddUnknownReferences(): void
    {
        $xml = <<<XML
            <references>
                <reference source="stuff1234" target="1"/>
            </references>
XML;
        $simple_xml         = new \SimpleXMLElement($xml);
        $created_references = array('package' => array('1' => '1337'));

        $this->dao->shouldReceive('getRef')->andReturns(\TestHelper::arrayToDar([]));

        $this->dao->shouldReceive('insertRef')->never();

        $this->importer->importCompatRefXML($this->configuration, \Mockery::spy(\Project::class), $simple_xml, $created_references);
    }
}
