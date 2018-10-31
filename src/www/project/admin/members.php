<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * http://sourceforge.net
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

use Tuleap\Project\Admin\ProjectMembers\ProjectMembersController;
use Tuleap\Project\Admin\ProjectMembers\ProjectMembersDAO;
use Tuleap\Project\Admin\ProjectMembers\ProjectMembersRouter;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemoverDao;

require_once('pre.php');
require_once('www/project/admin/project_admin_utils.php');
require_once('account.php');
require_once('common/include/TemplateSingleton.class.php');
require_once('common/tracker/ArtifactType.class.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');
require_once('www/project/admin/ugroup_utils.php');

$request = HTTPRequest::instance();

// Valid group id
$vGroupId = new Valid_GroupId();
$vGroupId->required();

if (! $request->valid($vGroupId)) {
    exit_error(
        $Language->getText('project_admin_index', 'invalid_p'),
        $Language->getText('project_admin_index', 'p_not_found')
    );
}

$group_id = $request->get('group_id');

$project_manager = ProjectManager::instance();
$project         = $project_manager->getProject($group_id);

if (! $project || ! is_object($project) || $project->isError()) {
    exit_no_group();
}

//must be a project admin
$membership_delegation_dao = new \Tuleap\Project\Admin\MembershipDelegationDao();
$user                      = $request->getCurrentUser();
if (! $user->isAdmin($group_id) && ! $membership_delegation_dao->doesUserHasMembershipDelegation($user->getId(), $group_id)) {
    exit_error(
        $Language->getText('include_session', 'insufficient_g_access'),
        $Language->getText('include_session', 'no_perm_to_view')
    );
}

//if the project isn't active, require you to be a member of the super-admin group
if ($project->getStatus() != 'A') {
    $request->checkUserIsSuperUser();
}

$ugroup_user_dao = new UGroupUserDao();
$ugroup_manager  = new UGroupManager();
$ugroup_binding  = new UGroupBinding(
    $ugroup_user_dao,
    $ugroup_manager
);

$project_manager     = ProjectManager::instance();
$event_manager       = EventManager::instance();
$type_factory        = new ArtifactTypeFactory(false);
$user_remover_dao    = new UserRemoverDao();
$user_manager        = UserManager::instance();
$project_history_dao = new ProjectHistoryDao();
$user_remover        = new UserRemover(
    $project_manager,
    $event_manager,
    $type_factory,
    $user_remover_dao,
    $user_manager,
    $project_history_dao,
    $ugroup_manager
);

$member_dao  = new ProjectMembersDAO();
$csrf_token  = new CSRFSynchronizerToken('/project/admin/members.php?group_id=' . urlencode($group_id));
$user_helper = new UserHelper();
$user_importer = new UserImport(
    $group_id,
    $user_manager,
    $user_helper
);

$member_controller = new ProjectMembersController(
    $member_dao,
    $csrf_token,
    $user_helper,
    $ugroup_binding,
    $user_remover,
    $event_manager,
    $ugroup_manager,
    $user_importer
);

$router = new ProjectMembersRouter(
    $member_controller,
    $csrf_token,
    $event_manager
);

$router->route($request);

project_admin_footer(array());
