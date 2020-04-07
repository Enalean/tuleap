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

use ArrayIterator;
use Docman_CloneItemsVisitor;
use Docman_ItemFactory;
use Docman_Link;
use Docman_LinkVersion;
use Docman_LinkVersionFactory;
use Docman_MetadataFactory;
use Docman_PermissionsManager;
use Docman_SettingsBo;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;

final class Docman_CloneItemsVisitorTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use MockeryPHPUnitIntegration;

    public function testLinkVersionIsCreatedWhenALinkIsCopied(): void
    {
        $project_manager      = Mockery::mock(ProjectManager::instance());
        $link_version_factory = Mockery::mock(Docman_LinkVersionFactory::class);

        $visitor = Mockery::mock(
            Docman_CloneItemsVisitor::class,
            [102, $project_manager, $link_version_factory]
        )->makePartial();
        $settings_bo = Mockery::mock(Docman_SettingsBo::class);
        $settings_bo->shouldReceive('getMetadataUsage')->andReturn(false);
        $visitor->shouldReceive('_getSettingsBo')->andReturn($settings_bo);
        $item_factory = Mockery::mock(Docman_ItemFactory::class);
        $item_factory->shouldReceive('rawCreate')->andReturn(743);
        $visitor->shouldReceive('_getItemFactory')->andReturn($item_factory);
        $permissions_manager = Mockery::mock(Docman_PermissionsManager::class);
        $permissions_manager->shouldReceive('cloneItemPermissions');
        $visitor->shouldReceive('_getPermissionsManager')->andReturn($permissions_manager);
        $metadata_factory = Mockery::mock(Docman_MetadataFactory::class);
        $metadata_factory->shouldReceive('appendItemMetadataList');
        $visitor->shouldReceive('_getMetadataFactory')->andReturn($metadata_factory);

        $link_to_copy = Mockery::mock(Docman_Link::class);
        $link_to_copy->shouldReceive('setGroupId');
        $link_to_copy->shouldReceive('setParentId');
        $link_to_copy->shouldReceive('getId')->andReturn(742);
        $link_to_copy->shouldReceive('getGroupId')->andReturn(102);
        $link_to_copy->shouldReceive('setStatus');
        $link_to_copy->shouldReceive('setObsolescenceDate');
        $link_to_copy->shouldReceive('toRow')->andReturn([]);
        $link_to_copy->shouldReceive('getMetadataIterator')->andReturn(new ArrayIterator());
        $copied_link = Mockery::mock(Docman_Link::class);
        $copied_link->shouldReceive('getTitle')->andReturn('Copied link title');
        $copied_link->shouldReceive('getGroupId')->andReturn(102);
        $copied_link_version = Mockery::mock(Docman_LinkVersion::class);
        $copied_link_version->shouldReceive('getNumber')->andReturn(12);
        $copied_link->shouldReceive('getCurrentVersion')->andReturn($copied_link_version);

        $item_factory->shouldReceive('getItemFromDb')->andReturn($copied_link);
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getPublicName')->andReturn('project name');
        $project_manager->shouldReceive('getProject')->andReturn($project);

        $link_version_factory->shouldReceive('create')->atLeast()->once();

        $visitor->visitLink(
            $link_to_copy,
            ['parentId' => 741, 'metadataMapping' => [], 'ugroupsMapping' => [], 'srcRootId' => 740]
        );
    }
}
