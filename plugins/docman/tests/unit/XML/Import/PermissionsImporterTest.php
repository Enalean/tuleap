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

use Docman_Item;
use PermissionsManager;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tuleap\Project\UGroupRetrieverWithLegacy;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionsImporterTest extends TestCase
{
    private PermissionsManager&MockObject $permission_manager;
    private UGroupRetrieverWithLegacy&MockObject $ugroup_retriever_with_legacy;
    private Project $project;
    private PermissionsImporter $importer;
    private Docman_Item $parent_item;
    private Docman_Item $item;

    protected function setUp(): void
    {
        $this->permission_manager           = $this->createMock(PermissionsManager::class);
        $this->ugroup_retriever_with_legacy = $this->createMock(UGroupRetrieverWithLegacy::class);
        $this->project                      = ProjectTestBuilder::aProject()->withPublicName('ACME Project')->build();

        $this->parent_item = new Docman_Item(['item_id' => 13]);
        $this->item        = new Docman_Item(['item_id' => 14]);

        $this->importer = new PermissionsImporter(
            new NullLogger(),
            $this->permission_manager,
            $this->ugroup_retriever_with_legacy,
            $this->project
        );
    }

    public function testItClonesPermissionsWhenNoPermissionsNode(): void
    {
        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="file">
                <properties>
                    <title>My document</title>
                </properties>
            </item>
            EOS
        );

        $this->permission_manager->expects($this->once())->method('clonePermissions')
            ->with(13, 14, ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE']);

        $this->importer->importPermissions($this->parent_item, $this->item, $xml);
    }

    public function testItSavesGivenPermissions(): void
    {
        $xml = new SimpleXMLElement(
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

        $this->ugroup_retriever_with_legacy->expects(self::exactly(2))->method('getUGroupId')
            ->willReturnCallback(static fn(Project $project, string $name) => match ($name) {
                'UGROUP_REGISTERED' => 2,
                'Developers'        => 101,
            });
        $matcher = self::exactly(2);

        $this->permission_manager->expects($matcher)->method('addPermission')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('PLUGIN_DOCMAN_READ', $parameters[0]);
                self::assertSame(14, $parameters[1]);
                self::assertSame(2, $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('PLUGIN_DOCMAN_WRITE', $parameters[0]);
                self::assertSame(14, $parameters[1]);
                self::assertSame(101, $parameters[2]);
            }
        });

        $this->importer->importPermissions($this->parent_item, $this->item, $xml);
    }

    public function testItIgnoresUnknownUgroup(): void
    {
        $xml = new SimpleXMLElement(
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

        $this->ugroup_retriever_with_legacy->expects($this->once())->method('getUGroupId')
            ->with($this->project, 'unknown')->willReturn(null);

        $this->permission_manager->expects(self::never())->method('addPermission');

        $this->importer->importPermissions($this->parent_item, $this->item, $xml);
    }
}
