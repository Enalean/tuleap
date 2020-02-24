<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\XML\Import;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PermissionsManager;
use PHPUnit\Framework\TestCase;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\Project\UGroupRetrieverWithLegacy;

class PermissionsImporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PermissionsManager
     */
    private $permission_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UGroupRetrieverWithLegacy
     */
    private $ugroup_retriever_with_legacy;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var PermissionsImporter
     */
    private $importer;
    /**
     * @var \Docman_Item|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $parent_item;
    /**
     * @var \Docman_Item|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $item;

    protected function setUp(): void
    {
        $this->logger                       = Mockery::mock(LoggerInterface::class);
        $this->permission_manager           = Mockery::mock(PermissionsManager::class);
        $this->ugroup_retriever_with_legacy = Mockery::mock(UGroupRetrieverWithLegacy::class);
        $this->project                      = Mockery::mock(Project::class);

        $this->project->shouldReceive(['getPublicName' => 'ACME Project']);

        $this->parent_item = Mockery::mock(\Docman_Item::class)->shouldReceive(['getId' => 13])->getMock();
        $this->item        = Mockery::mock(\Docman_Item::class)->shouldReceive(['getId' => 14])->getMock();

        $this->importer = new PermissionsImporter(
            $this->logger,
            $this->permission_manager,
            $this->ugroup_retriever_with_legacy,
            $this->project
        );
    }

    public function testItClonesPermissionsWhenNoPermissionsNode(): void
    {
        $xml = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="file">
                <properties>
                    <title>My document</title>
                </properties>
            </item>
            EOS
        );

        $this->permission_manager
            ->shouldReceive('clonePermissions')
            ->with(13, 14, ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE'])
            ->once();

        $this->importer->importPermissions($this->parent_item, $this->item, $xml);
    }

    public function testItSavesGivenPermissions(): void
    {
        $xml = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="file">
                <properties>
                    <title>My document</title>
                </properties>
                <permissions>
                    <permission type="PLUGIN_DOCMAN_READ" ugroup="UGROUP_REGISTERED"/>
                    <permission type="PLUGIN_DOCMAN_WRITE" ugroup="Developers"/>
                </permissions>
            </item>
            EOS
        );

        $this->ugroup_retriever_with_legacy
            ->shouldReceive('getUGroupId')
            ->with($this->project, 'UGROUP_REGISTERED')
            ->once()
            ->andReturn(2);
        $this->ugroup_retriever_with_legacy
            ->shouldReceive('getUGroupId')
            ->with($this->project, 'Developers')
            ->once()
            ->andReturn(101);

        $this->permission_manager
            ->shouldReceive('addPermission')
            ->with('PLUGIN_DOCMAN_READ', 14, 2)
            ->once();
        $this->permission_manager
            ->shouldReceive('addPermission')
            ->with('PLUGIN_DOCMAN_WRITE', 14, 101)
            ->once();

        $this->importer->importPermissions($this->parent_item, $this->item, $xml);
    }

    public function testItIgnoresUnknownUgroup(): void
    {
        $xml = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="file">
                <properties>
                    <title>My document</title>
                </properties>
                <permissions>
                    <permission type="PLUGIN_DOCMAN_READ" ugroup="unknown"/>
                </permissions>
            </item>
            EOS
        );

        $this->ugroup_retriever_with_legacy
            ->shouldReceive('getUGroupId')
            ->with($this->project, 'unknown')
            ->once()
            ->andReturnNull();

        $this->permission_manager
            ->shouldReceive('addPermission')
            ->never();

        $this->logger->shouldReceive('error')->once();

        $this->importer->importPermissions($this->parent_item, $this->item, $xml);
    }
}
