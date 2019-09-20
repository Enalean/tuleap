<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../../bootstrap.php';
require_once dirname(__FILE__).'/../../../../../ldap/include/LDAP_User.class.php';

interface Git_Driver_Gerrit_addIncludedGroupTest
{
    public function itAddAnIncludedGroup();
}

interface Git_Driver_Gerrit_removeIncludedGroupTest
{
    public function itRemovesAllIncludedGroups();
}

interface Git_Driver_Gerrit_projectExistsTest
{
    public function itReturnsTrueIfParentProjectExists();
    public function itReturnsFalseIfParentProjectDoNotExists();
}

interface Git_Driver_Gerrit_manageProjectsTest
{
    public function itExecutesTheCreateCommandForProjectOnTheGerritServer();
    public function itExecutesTheCreateCommandForParentProjectOnTheGerritServer();
    public function itReturnsTheNameOfTheCreatedProject();
    public function itRaisesAGerritDriverExceptionOnProjectCreation();
    public function itDoesntTransformExceptionsThatArentRelatedToGerrit();
    public function itInformsAboutProjectInitialization();
}

interface Git_Driver_Gerrit_groupExistsTest
{
    public function itReturnsTrueIfGroupExists();
    public function itReturnsFalseIfGroupDoNotExists();
}

interface Git_Driver_Gerrit_DeletePluginTest
{
    public function itReturnsFalseIfPluginIsNotInstalled();
    public function itReturnsFalseIfPluginIsInstalledAndNotEnabled();
    public function itReturnsTrueIfPluginIsInstalledAndEnabled();
    public function itThrowsAProjectDeletionExceptionIfThereAreOpenChanges();
}

interface Git_Driver_Gerrit_manageGroupsTest
{
    public function itCreatesGroupsIfItNotExistsOnGerrit();
    public function itDoesNotCreateGroupIfItAlreadyExistsOnGerrit();
    public function itInformsAboutGroupCreation();
    public function itRaisesAGerritDriverExceptionOnGroupsCreation();
    public function itCreatesGroupWithoutOwnerWhenSelfOwnedToAvoidChickenEggIssue();
    public function itAsksGerritForTheGroupUUID();
    public function itReturnsNullUUIDIfNotFound();
    public function itAsksGerritForTheGroupId();
    public function itReturnsNullIdIfNotFound();
    public function itReturnsAllGroups();
}

interface Git_Driver_Gerrit_manageUserTest
{
    public function itExecutesTheInsertCommand();
    public function itExecutesTheDeletionCommand();
    public function itRemovesAllMembers();
}
