<?php
/**
 * Copyright (c) Enalean, 2012 - 2015. All Rights Reserved.
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

require_once 'bootstrap.php';

class Docman_PermissionsItemManager_Test extends TuleapTestCase {
    protected $permissions_manager;
    protected $project_manager;
    protected $project;
    protected $docman_item;
    protected $item_id = 100;
    protected $uniq_id = 200;
    protected $literalizer;
    public const PERMISSIONS_TYPE = Docman_PermissionsItemManager::PERMISSIONS_TYPE;

    public function setUp() {
        parent::setUp();
        $this->literalizer         = new UGroupLiteralizer();
        $this->docman_item         = new Docman_Item();
        $this->docman_item->setId($this->item_id);
        $this->permissions_manager = mock('PermissionsManager');
        $this->project_manager     = mock('ProjectManager');
        $this->project             = mock('Project');
        $this->docman_permissions  = new Docman_PermissionsItemManager();

        stub($this->project_manager)->getProject()->returns($this->project);
        stub($this->project)->getUnixName()->returns('gpig');
        stub($this->project)->getID()->returns($this->uniqId());

        stub($this->permissions_manager)->getAuthorizedUgroupIds('*', 'PLUGIN_DOCMAN_ADMIN')->returns(array(114));

        PermissionsManager::setInstance($this->permissions_manager);
        ProjectManager::setInstance($this->project_manager);
        Docman_ItemFactory::setInstance($this->project->getID(), mock('Docman_ItemFactory'));
    }

    public function tearDown() {
        parent::tearDown();
        $this->item_id++;
        PermissionsManager::clearInstance();
        ProjectManager::clearInstance();
        Docman_ItemFactory::clearInstance($this->project->getID());
    }

    private function anItem() {
        $item = new Docman_Item();
        $item->setId($this->uniqId());
        return $item;
    }

    private function uniqId() {
        $this->uniq_id++;
        return $this->item_id + $this->uniq_id;
    }

    public function itReturnsPermissionsThanksToPermissionsManager() {
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject()->returns(array(1));
        $this->permissions_manager->expectOnce('getAuthorizedUGroupIdsForProject', array($this->project, $this->item_id, self::PERMISSIONS_TYPE));
        $this->docman_permissions->exportPermissions($this->docman_item);
    }

    public function itAsksForDocmanAdminGroupIfNoPermissionSet() {
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject()->returns(array());
        $this->permissions_manager->expectOnce('getAuthorizedUGroupIdsForProject', array($this->project, $this->item_id, self::PERMISSIONS_TYPE));
        $this->permissions_manager->expectOnce('getAuthorizedUgroupIds', array('*', 'PLUGIN_DOCMAN_ADMIN'));
        $this->docman_permissions->exportPermissions($this->docman_item);
    }

    public function itReturnsValueOfExternalPermissions_GetProjectObjectGroupsIfItHasNoParents() {
        $permissions = array(ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN, 103);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject()->returns($permissions);

        $expected    = $this->literalizer->ugroupIdsToString($permissions, $this->project);
        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);
        $this->assertEqual($expected, $permissions);
    }

    public function ItReturnsTheMembersUgroupWhenItemContainsRegisteredUgroupAndParentContainsTheMembersUgroup() {
        $parent    = $this->anItem();
        $parent_id = $parent->getId();
        $this->docman_item->setParentId($parent_id);

        $parent_permissions = array(ProjectUGroup::PROJECT_MEMBERS);
        $child_permissions  = array(ProjectUGroup::REGISTERED);

        stub(Docman_ItemFactory::instance($this->project->getID()))->getItemFromDb($parent_id)->returns($parent);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $parent_id,     self::PERMISSIONS_TYPE)->returns($parent_permissions);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $this->item_id, self::PERMISSIONS_TYPE)->returns($child_permissions);

        $expected    = $this->literalizer->ugroupIdsToString($parent_permissions, $this->project);
        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);
        $this->assertEqual($expected, $permissions);
    }

    public function itReturnsTheMembersUgroupWhenItemContainsTheRegisteredUgroupAndParentOfParentContainsMembers() {
        $project_id       = $this->project->getID();
        $parent           = $this->anItem();
        $parent_id        = $parent->getId();
        $parent_parent    = $this->anItem();
        $parent_parent_id = $parent_parent->getId();
        $this->docman_item->setParentId($parent_id);
        $parent->setParentId($parent_parent_id);

        $parent_parent_permissions = array(ProjectUGroup::PROJECT_MEMBERS);
        $parent_permissions        = array(ProjectUGroup::REGISTERED);
        $child_permissions         = array(ProjectUGroup::REGISTERED);

        stub(Docman_ItemFactory::instance($project_id))->getItemFromDb($parent_id)->returns($parent);
        stub(Docman_ItemFactory::instance($project_id))->getItemFromDb($parent_parent_id)->returns($parent_parent);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $parent_parent_id, self::PERMISSIONS_TYPE)->returns($parent_parent_permissions);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $parent_id,        self::PERMISSIONS_TYPE)->returns($parent_permissions);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $this->item_id,    self::PERMISSIONS_TYPE)->returns($child_permissions);

        $expected    = $this->literalizer->ugroupIdsToString($parent_parent_permissions, $this->project);
        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);
        $this->assertEqual($expected, $permissions);
    }


    public function itReturnsTheMembersUgroupWhenItemContainsTheMembersUgroupAndParentContainsTheRegisteredUgroup() {
        $parent    = $this->anItem();
        $parent_id = $parent->getId();
        $this->docman_item->setParentId($parent_id);

        $parent_permissions = array(ProjectUGroup::REGISTERED);
        $child_permissions  = array(ProjectUGroup::PROJECT_MEMBERS);

        stub(Docman_ItemFactory::instance($this->project->getID()))->getItemFromDb($parent_id)->returns($parent);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $parent_id,     self::PERMISSIONS_TYPE)->returns($parent_permissions);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $this->item_id, self::PERMISSIONS_TYPE)->returns($child_permissions);

        $expected    = $this->literalizer->ugroupIdsToString($child_permissions, $this->project);
        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);

        $this->assertEqual($expected, $permissions);
    }

    public function itReturnsAllItemUgroupsWhenTheyAreAsRestrictiveAsParentAndHaveMoreUgroups() {
        $parent    = $this->anItem();
        $parent_id = $parent->getId();
        $this->docman_item->setParentId($parent_id);

        $parent_permissions = array(ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_MEMBERS);
        $child_permissions  = array(ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN);

        stub(Docman_ItemFactory::instance($this->project->getID()))->getItemFromDb($parent_id)->returns($parent);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $parent_id,     self::PERMISSIONS_TYPE)->returns($parent_permissions);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $this->item_id, self::PERMISSIONS_TYPE)->returns($child_permissions);

        $expected    = $this->literalizer->ugroupIdsToString($child_permissions, $this->project);
        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);

        $this->assertEqual($expected, $permissions);
    }

    public function itReturnsAllParentUgroupsWhenTheyAreAsRestrictiveAsItemUgroupsAndHaveMoreUgroups() {
        $parent    = $this->anItem();
        $parent_id = $parent->getId();
        $this->docman_item->setParentId($parent_id);

        $parent_permissions  = array(ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN);
        $child_permissions = array(ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_MEMBERS);

        stub(Docman_ItemFactory::instance($this->project->getID()))->getItemFromDb($parent_id)->returns($parent);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $parent_id,     self::PERMISSIONS_TYPE)->returns($parent_permissions);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $this->item_id, self::PERMISSIONS_TYPE)->returns($child_permissions);

        $expected    = $this->literalizer->ugroupIdsToString($parent_permissions, $this->project);
        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);

        $this->assertEqual($expected, $permissions);
    }

    public function itReturnsStaticGroupIfPresentInParent() {
        $parent    = $this->anItem();
        $parent_id = $parent->getId();
        $this->docman_item->setParentId($parent_id);

        $parent_permissions = array(102, 103);
        $child_permissions  = array(102, 104);

        stub(Docman_ItemFactory::instance($this->project->getID()))->getItemFromDb($parent_id)->returns($parent);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $parent_id,     self::PERMISSIONS_TYPE)->returns($parent_permissions);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $this->item_id, self::PERMISSIONS_TYPE)->returns($child_permissions);

        $expected    = $this->literalizer->ugroupIdsToString(array(102), $this->project);
        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);

        $this->assertEqual($expected, $permissions);
    }

    public function itReturnsGroupsOfChildIfParentIsPublic() {
        $parent    = $this->anItem();
        $parent_id = $parent->getId();
        $this->docman_item->setParentId($parent_id);

        $parent_permissions = array(1);
        $child_permissions  = array(102, 103, ProjectUGroup::REGISTERED);

        stub(Docman_ItemFactory::instance($this->project->getID()))->getItemFromDb($parent_id)->returns($parent);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $parent_id,     self::PERMISSIONS_TYPE)->returns($parent_permissions);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $this->item_id, self::PERMISSIONS_TYPE)->returns($child_permissions);

        $expected    = $this->literalizer->ugroupIdsToString($child_permissions, $this->project);
        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);

        $this->assertEqual($expected, $permissions);
    }

    public function itReturnsGroupsOfParentIfChildIsPublic() {
        $parent    = $this->anItem();
        $parent_id = $parent->getId();
        $this->docman_item->setParentId($parent_id);

        $parent_permissions = array(101, 102, ProjectUGroup::REGISTERED);
        $child_permissions  = array(1);

        stub(Docman_ItemFactory::instance($this->project->getID()))->getItemFromDb($parent_id)->returns($parent);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $parent_id,     self::PERMISSIONS_TYPE)->returns($parent_permissions);
        stub($this->permissions_manager)->getAuthorizedUGroupIdsForProject($this->project, $this->item_id, self::PERMISSIONS_TYPE)->returns($child_permissions);

        $expected    = $this->literalizer->ugroupIdsToString($parent_permissions, $this->project);
        $permissions = $this->docman_permissions->exportPermissions($this->docman_item);
        $this->assertEqual($expected, $permissions);
    }
}
