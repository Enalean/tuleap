<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\PermissionManager;

use Docman_Item;
use Docman_ItemFactory;
use Docman_PermissionsItemManager;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;
use UGroupLiteralizer;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_PermissionsItemManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected $permissions_manager;
    protected $project_manager;
    protected $project;
    protected $docman_item;
    protected $item_id = 100;
    protected $uniq_id = 200;
    protected $literalizer;
    public const PERMISSIONS_TYPE = Docman_PermissionsItemManager::PERMISSIONS_TYPE;
    private $docman_permissions;
    /**
     * @var Docman_ItemFactory|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $item_factory;

    public function setUp(): void
    {
        parent::setUp();
        $this->literalizer         = \Mockery::mock(UGroupLiteralizer::class);
        $this->docman_item         = new Docman_Item();
        $this->docman_item->setId($this->item_id);
        $this->permissions_manager = \Mockery::mock(\PermissionsManager::class);
        $this->project_manager     = \Mockery::mock(\ProjectManager::class);
        $this->project             = \Mockery::mock(\Project::class);
        $this->item_factory        = \Mockery::mock(Docman_ItemFactory::class);
        $this->docman_permissions  = \Mockery::mock(
            'Docman_PermissionsItemManager[getUGroupLiteralizer,getProjectManager, getPermissionsManager, getDocmanItemFactory]'
        )->shouldAllowMockingProtectedMethods();

        $this->project_manager->shouldReceive('getProject')->andReturns($this->project);
        $this->project->shouldReceive('getUnixName')->andReturns('gpig');
        $this->project->shouldReceive('getID')->andReturns($this->uniqId());
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->item_id++;
    }

    private function anItem()
    {
        $item = new Docman_Item();
        $item->setId($this->uniqId());
        return $item;
    }

    private function uniqId()
    {
        $this->uniq_id++;
        return $this->item_id + $this->uniq_id;
    }

    public function testItReturnsPermissionsThanksToPermissionsManager():void
    {
        $this->docman_permissions->shouldReceive('getProjectManager')->andReturn($this->project_manager);

        $this->permissions_manager->shouldReceive('getAuthorizedUgroupIds')->never();

        $this->docman_permissions->shouldReceive('getUGroupLiteralizer')->andReturn($this->literalizer);

        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $this->docman_item->getId(),
            self::PERMISSIONS_TYPE
        )->andReturn([114]);

        $this->literalizer->shouldReceive('ugroupIdsToString')->with([114], $this->project)->andReturn(['@ug_114']);

        $permission = $this->docman_permissions->exportPermissions($this->docman_item);
        $expected_permission = ['@ug_114'];
        $this->assertEquals($expected_permission, $permission);
    }

    public function testItAsksForDocmanAdminGroupIfNoPermissionSet(): void
    {
        $this->docman_permissions->shouldReceive('getProjectManager')->andReturn($this->project_manager);
        $this->docman_permissions->shouldReceive('getPermissionsManager')->andReturn($this->permissions_manager);
        $this->docman_permissions->shouldReceive('getUGroupLiteralizer')->andReturn($this->literalizer);

        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $this->docman_item->getId(),
            self::PERMISSIONS_TYPE
        )->andReturn([]);

        $this->permissions_manager->shouldReceive('getAuthorizedUgroupIds')->with(
            $this->project->getID(),
            'PLUGIN_DOCMAN_ADMIN'
        )->andReturns([114]);

        $this->literalizer->shouldReceive('ugroupIdsToString')->with([114], $this->project)->andReturn(['@ug_114']);

        $permission          = $this->docman_permissions->exportPermissions($this->docman_item);
        $expected_permission = ['@ug_114'];
        $this->assertEquals($expected_permission, $permission);
    }

    public function testItReturnsValueOfExternalPermissionsGetProjectObjectGroupsIfItHasNoParents(): void
    {
        $this->docman_permissions->shouldReceive('getProjectManager')->andReturn($this->project_manager);
        $this->docman_permissions->shouldReceive('getPermissionsManager')->andReturn($this->permissions_manager);
        $this->docman_permissions->shouldReceive('getUGroupLiteralizer')->andReturn($this->literalizer);

        $permissions = [ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN, 103];
        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $this->docman_item->getId(),
            self::PERMISSIONS_TYPE
        )->andReturn($permissions);

        $this->literalizer->shouldReceive('ugroupIdsToString')->with($permissions, $this->project)->andReturn(
            [
                '@site_active',
                '@gpig_project_members',
                '@gpig_project_admin',
                '@ug_103'
            ]
        );

        $expected_ugroup_literalizer = new UGroupLiteralizer();
        $expected                    = $expected_ugroup_literalizer->ugroupIdsToString($permissions, $this->project);

        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);
        $this->assertEquals($expected, $permissions);
    }

    public function testItReturnsTheMembersUgroupWhenItemContainsRegisteredUgroupAndParentContainsTheMembersUgroup(): void
    {
        $this->docman_permissions->shouldReceive('getProjectManager')->andReturn($this->project_manager);
        $this->docman_permissions->shouldReceive('getPermissionsManager')->andReturn($this->permissions_manager);
        $this->docman_permissions->shouldReceive('getUGroupLiteralizer')->andReturn($this->literalizer);
        $this->docman_permissions->shouldReceive('getDocmanItemFactory')->with($this->project)->andReturn(
            $this->item_factory
        );

        $parent    = $this->anItem();
        $parent_id = $parent->getId();
        $this->docman_item->setParentId($parent_id);

        $parent_permissions = array(ProjectUGroup::PROJECT_MEMBERS);
        $child_permissions  = array(ProjectUGroup::REGISTERED);

        $this->item_factory->shouldReceive('getItemFromDb')->with($parent_id)->andReturn($parent)->once();

        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $this->docman_item->getId(),
            self::PERMISSIONS_TYPE
        )->andReturn($child_permissions);

        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $parent_id,
            self::PERMISSIONS_TYPE
        )->andReturn($parent_permissions);

        $this->literalizer->shouldReceive('ugroupIdsToString')->with($parent_permissions, $this->project)->andReturn(
            [
                '@gpig_project_members'
            ]
        );

        $expected_ugroup_literalizer = new UGroupLiteralizer();
        $expected                    = $expected_ugroup_literalizer->ugroupIdsToString(
            $parent_permissions,
            $this->project
        );
        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);
        $this->assertEquals($expected, $permissions);
    }

    public function testItReturnsTheMembersUgroupWhenItemContainsTheRegisteredUgroupAndParentOfParentContainsMembers(): void
    {
        $this->docman_permissions->shouldReceive('getProjectManager')->andReturn($this->project_manager);
        $this->docman_permissions->shouldReceive('getPermissionsManager')->andReturn($this->permissions_manager);
        $this->docman_permissions->shouldReceive('getUGroupLiteralizer')->andReturn($this->literalizer);
        $this->docman_permissions->shouldReceive('getDocmanItemFactory')->with($this->project)->andReturn(
            $this->item_factory
        );

        $parent           = $this->anItem();
        $parent_id        = $parent->getId();
        $parent_parent    = $this->anItem();
        $parent_parent_id = $parent_parent->getId();
        $this->docman_item->setParentId($parent_id);
        $parent->setParentId($parent_parent_id);

        $parent_parent_permissions = array(ProjectUGroup::PROJECT_MEMBERS);
        $parent_permissions        = array(ProjectUGroup::REGISTERED);
        $child_permissions         = array(ProjectUGroup::REGISTERED);

        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $parent_parent_id,
            self::PERMISSIONS_TYPE
        )->andReturns($parent_parent_permissions);
        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $parent_id,
            self::PERMISSIONS_TYPE
        )->andReturns($parent_permissions);
        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $this->item_id,
            self::PERMISSIONS_TYPE
        )->andReturns($child_permissions);

        $this->item_factory->shouldReceive('getItemFromDb')->with($parent_parent_id)->andReturn($parent_parent)->once();
        $this->item_factory->shouldReceive('getItemFromDb')->with($parent_id)->andReturn($parent)->once();

        $this->literalizer->shouldReceive('ugroupIdsToString')->with(
            $parent_parent_permissions,
            $this->project
        )->andReturn(
            [
                '@gpig_project_members'
            ]
        );

        $expected_ugroup_literalizer = new UGroupLiteralizer();
        $expected                    = $expected_ugroup_literalizer->ugroupIdsToString(
            $parent_parent_permissions,
            $this->project
        );

        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);
        $this->assertEquals($expected, $permissions);
    }


    public function testItReturnsTheMembersUgroupWhenItemContainsTheMembersUgroupAndParentContainsTheRegisteredUgroup(): void
    {
        $this->docman_permissions->shouldReceive('getProjectManager')->andReturn($this->project_manager);
        $this->docman_permissions->shouldReceive('getPermissionsManager')->andReturn($this->permissions_manager);
        $this->docman_permissions->shouldReceive('getUGroupLiteralizer')->andReturn($this->literalizer);
        $this->docman_permissions->shouldReceive('getDocmanItemFactory')->with($this->project)->andReturn(
            $this->item_factory
        );

        $parent    = $this->anItem();
        $parent_id = $parent->getId();
        $this->docman_item->setParentId($parent_id);

        $parent_permissions = array(ProjectUGroup::REGISTERED);
        $child_permissions  = array(ProjectUGroup::PROJECT_MEMBERS);

        $this->literalizer->shouldReceive('getUgroupIds')->with($this->project, $parent_id, self::PERMISSIONS_TYPE)->andReturns($parent_permissions);
        $this->literalizer->shouldReceive('getUgroupIds')->with($this->project, $this->item_id, self::PERMISSIONS_TYPE)->andReturns($child_permissions);

        $this->item_factory->shouldReceive('getItemFromDb')->with($parent_id)->andReturn($parent)->once();

        $this->literalizer->shouldReceive('ugroupIdsToString')->with($child_permissions, $this->project)->andReturn(
            [
                '@gpig_project_members'
            ]
        );

        $expected_ugroup_literalizer = new UGroupLiteralizer();
        $expected                    = $expected_ugroup_literalizer->ugroupIdsToString(
            $child_permissions,
            $this->project
        );

        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);

        $this->assertEquals($expected, $permissions);
    }

    public function testItReturnsAllItemUgroupsWhenTheyAreAsRestrictiveAsParentAndHaveMoreUgroups(): void
    {
        $this->docman_permissions->shouldReceive('getProjectManager')->andReturn($this->project_manager);
        $this->docman_permissions->shouldReceive('getPermissionsManager')->andReturn($this->permissions_manager);
        $this->docman_permissions->shouldReceive('getUGroupLiteralizer')->andReturn($this->literalizer);
        $this->docman_permissions->shouldReceive('getDocmanItemFactory')->with($this->project)->andReturn(
            $this->item_factory
        );

        $parent    = $this->anItem();
        $parent_id = $parent->getId();
        $this->docman_item->setParentId($parent_id);

        $parent_permissions = array(ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_MEMBERS);
        $child_permissions  = array(ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN);

        $this->item_factory->shouldReceive('getItemFromDb')->with($parent_id)->andReturn($parent)->once();

        $this->literalizer->shouldReceive('getUgroupIds')->with($this->project, $parent_id, self::PERMISSIONS_TYPE)->andReturns($parent_permissions);
        $this->literalizer->shouldReceive('getUgroupIds')->with($this->project, $this->item_id, self::PERMISSIONS_TYPE)->andReturns($child_permissions);

        $this->literalizer->shouldReceive('ugroupIdsToString')->with($child_permissions, $this->project)->andReturn(
            [
                '@site_active',
                '@gpig_project_members',
                '@gpig_project_admin'
            ]
        );

        $expected_ugroup_literalizer = new UGroupLiteralizer();
        $expected                    = $expected_ugroup_literalizer->ugroupIdsToString(
            $child_permissions,
            $this->project
        );
        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);

        $this->assertEquals($expected, $permissions);
    }

    public function testItReturnsAllParentUgroupsWhenTheyAreAsRestrictiveAsItemUgroupsAndHaveMoreUgroups(): void
    {
        $this->docman_permissions->shouldReceive('getProjectManager')->andReturn($this->project_manager);
        $this->docman_permissions->shouldReceive('getPermissionsManager')->andReturn($this->permissions_manager);
        $this->docman_permissions->shouldReceive('getUGroupLiteralizer')->andReturn($this->literalizer);
        $this->docman_permissions->shouldReceive('getDocmanItemFactory')->with($this->project)->andReturn(
            $this->item_factory
        );

        $parent    = $this->anItem();
        $parent_id = $parent->getId();
        $this->docman_item->setParentId($parent_id);

        $parent_permissions  = array(ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN);
        $child_permissions = array(ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_MEMBERS);

        $this->item_factory->shouldReceive('getItemFromDb')->with($parent_id)->andReturn($parent)->once();

        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $parent_id,
            self::PERMISSIONS_TYPE
        )->andReturns($parent_permissions);
        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $this->item_id,
            self::PERMISSIONS_TYPE
        )->andReturns($child_permissions);

        $this->literalizer->shouldReceive('ugroupIdsToString')->with($parent_permissions, $this->project)->andReturn(
            [
                '@site_active',
                '@gpig_project_members',
                '@gpig_project_admin'
            ]
        );

        $expected_ugroup_literalizer = new UGroupLiteralizer();
        $expected                    = $expected_ugroup_literalizer->ugroupIdsToString(
            $parent_permissions,
            $this->project
        );
        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);

        $this->assertEquals($expected, $permissions);
    }

    public function testItReturnsStaticGroupIfPresentInParent(): void
    {
        $this->docman_permissions->shouldReceive('getProjectManager')->andReturn($this->project_manager);
        $this->docman_permissions->shouldReceive('getPermissionsManager')->andReturn($this->permissions_manager);
        $this->docman_permissions->shouldReceive('getUGroupLiteralizer')->andReturn($this->literalizer);
        $this->docman_permissions->shouldReceive('getDocmanItemFactory')->with($this->project)->andReturn(
            $this->item_factory
        );

        $parent    = $this->anItem();
        $parent_id = $parent->getId();
        $this->docman_item->setParentId($parent_id);

        $parent_permissions = array(102, 103);
        $child_permissions  = array(102, 104);

        $this->item_factory->shouldReceive('getItemFromDb')->with($parent_id)->andReturn($parent)->once();

        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $parent_id,
            self::PERMISSIONS_TYPE
        )->andReturns($parent_permissions);
        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $this->item_id,
            self::PERMISSIONS_TYPE
        )->andReturns($child_permissions);

        $this->literalizer->shouldReceive('ugroupIdsToString')->with([102], $this->project)->andReturn(
            [
                '@ug_102'
            ]
        );

        $expected_ugroup_literalizer = new UGroupLiteralizer();
        $expected                    = $expected_ugroup_literalizer->ugroupIdsToString(
            [102],
            $this->project
        );

        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);

        $this->assertEquals($expected, $permissions);
    }

    public function testItReturnsGroupsOfChildIfParentIsPublic(): void
    {
        $this->docman_permissions->shouldReceive('getProjectManager')->andReturn($this->project_manager);
        $this->docman_permissions->shouldReceive('getPermissionsManager')->andReturn($this->permissions_manager);
        $this->docman_permissions->shouldReceive('getUGroupLiteralizer')->andReturn($this->literalizer);
        $this->docman_permissions->shouldReceive('getDocmanItemFactory')->with($this->project)->andReturn(
            $this->item_factory
        );

        $parent    = $this->anItem();
        $parent_id = $parent->getId();
        $this->docman_item->setParentId($parent_id);

        $parent_permissions = array(1);
        $child_permissions  = array(102, 103, ProjectUGroup::REGISTERED);

        $this->item_factory->shouldReceive('getItemFromDb')->with($parent_id)->andReturn($parent)->once();

        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $parent_id,
            self::PERMISSIONS_TYPE
        )->andReturns($parent_permissions);
        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $this->item_id,
            self::PERMISSIONS_TYPE
        )->andReturns($child_permissions);

        $this->literalizer->shouldReceive('ugroupIdsToString')->with($child_permissions, $this->project)->andReturn(
            [
                '@ug_102',
                '@ug_103',
                '@site_active',
                '@gpig_project_members'
            ]
        );

        $expected_ugroup_literalizer = new UGroupLiteralizer();
        $expected                    = $expected_ugroup_literalizer->ugroupIdsToString(
            $child_permissions,
            $this->project
        );

        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);

        $this->assertEquals($expected, $permissions);
    }

    public function testItReturnsGroupsOfParentIfChildIsPublic(): void
    {
        $this->docman_permissions->shouldReceive('getProjectManager')->andReturn($this->project_manager);
        $this->docman_permissions->shouldReceive('getPermissionsManager')->andReturn($this->permissions_manager);
        $this->docman_permissions->shouldReceive('getUGroupLiteralizer')->andReturn($this->literalizer);
        $this->docman_permissions->shouldReceive('getDocmanItemFactory')->with($this->project)->andReturn(
            $this->item_factory
        );

        $parent    = $this->anItem();
        $parent_id = $parent->getId();
        $this->docman_item->setParentId($parent_id);

        $parent_permissions = array(101, 102, ProjectUGroup::REGISTERED);
        $child_permissions  = array(1);

        $this->item_factory->shouldReceive('getItemFromDb')->with($parent_id)->andReturn($parent)->once();

        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $parent_id,
            self::PERMISSIONS_TYPE
        )->andReturns($parent_permissions);
        $this->literalizer->shouldReceive('getUgroupIds')->with(
            $this->project,
            $this->item_id,
            self::PERMISSIONS_TYPE
        )->andReturns($child_permissions);

        $this->literalizer->shouldReceive('ugroupIdsToString')->with($parent_permissions, $this->project)->andReturn(
            [
                '@ug_101',
                '@ug_102',
                '@site_active',
                '@gpig_project_members'
            ]
        );

        $expected_ugroup_literalizer = new UGroupLiteralizer();
        $expected                    = $expected_ugroup_literalizer->ugroupIdsToString(
            $parent_permissions,
            $this->project
        );

        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);
        $this->assertEquals($expected, $permissions);
    }
}
