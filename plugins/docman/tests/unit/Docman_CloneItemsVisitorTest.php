<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman;

use Docman_CloneItemsVisitor;
use Docman_ItemFactory;
use Docman_Link;
use Docman_LinkVersion;
use Docman_LinkVersionFactory;
use Docman_MetadataFactory;
use Docman_PermissionsManager;
use Docman_SettingsBo;
use ProjectManager;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Docman_CloneItemsVisitorTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testLinkVersionIsCreatedWhenALinkIsCopied(): void
    {
        $project_manager      = $this->createMock(ProjectManager::class);
        $link_version_factory = $this->createMock(Docman_LinkVersionFactory::class);

        $visitor     = $this->getMockBuilder(Docman_CloneItemsVisitor::class)
            ->setConstructorArgs([102, $project_manager, $link_version_factory, EventDispatcherStub::withIdentityCallback()])
            ->onlyMethods([
                '_getSettingsBo',
                '_getItemFactory',
                '_getPermissionsManager',
                '_getMetadataFactory',
            ])
            ->getMock();
        $settings_bo = $this->createMock(Docman_SettingsBo::class);
        $settings_bo->method('getMetadataUsage')->willReturn(false);
        $visitor->method('_getSettingsBo')->willReturn($settings_bo);
        $item_factory = $this->createMock(Docman_ItemFactory::class);
        $item_factory->method('rawCreate')->willReturn(743);
        $visitor->method('_getItemFactory')->willReturn($item_factory);
        $permissions_manager = $this->createMock(Docman_PermissionsManager::class);
        $permissions_manager->method('cloneItemPermissions');
        $visitor->method('_getPermissionsManager')->willReturn($permissions_manager);
        $metadata_factory = $this->createMock(Docman_MetadataFactory::class);
        $metadata_factory->method('appendItemMetadataList');
        $visitor->method('_getMetadataFactory')->willReturn($metadata_factory);

        $link_to_copy        = new Docman_Link(['item_id' => 742, 'group_id' => 102, 'link_url' => '']);
        $copied_link         = new Docman_Link(['group_id' => 102, 'title' => 'Copied link title', 'link_url' => '']);
        $copied_link_version = $this->createMock(Docman_LinkVersion::class);
        $copied_link_version->method('getNumber')->willReturn(12);
        $copied_link->setCurrentVersion($copied_link_version);

        $item_factory->method('getItemFromDb')->willReturn($copied_link);
        $project = ProjectTestBuilder::aProject()->withPublicName('project name')->build();
        $project_manager->method('getProject')->willReturn($project);

        $link_version_factory->expects(self::atLeastOnce())->method('create');

        $visitor->visitLink(
            $link_to_copy,
            ['parentId' => 741, 'metadataMapping' => [], 'ugroupsMapping' => [], 'srcRootId' => 740]
        );
    }
}
