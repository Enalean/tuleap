<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Git\Permissions;

use Git;
use Codendi_Request;
use ProjectHistoryDao;
use Feedback;
use PermissionsManager;
use CSRFSynchronizerToken;
use Project;

class TemplatePermissionsUpdater
{

    public const REQUEST_KEY = 'default_access_rights';

    /**
     * @var PermissionChangesDetector
     */
    private $permission_changes_detector;

    /**
     * @var TemplateFineGrainedPermissionSaver
     */
    private $template_fine_grained_saver;

    /**
     * @var FineGrainedUpdater
     */
    private $fine_grained_updater;

    /**
     * @var DefaultFineGrainedPermissionFactory
     */
    private $default_fine_grained_factory;

    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;

    /**
     * @var HistoryValueFormatter
     */
    private $history_value_formatter;

    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;

    /**
     * @var RegexpFineGrainedEnabler
     */
    private $regexp_enabler;

    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_retriever;
    /**
     * @var RegexpPermissionFilter
     */
    private $permission_filter;
    /**
     * @var RegexpFineGrainedDisabler
     */
    private $regexp_disabler;

    public function __construct(
        PermissionsManager $permissions_manager,
        ProjectHistoryDao $history_dao,
        HistoryValueFormatter $history_value_formatter,
        FineGrainedRetriever $fine_grained_retriever,
        DefaultFineGrainedPermissionFactory $default_fine_grained_factory,
        FineGrainedUpdater $fine_grained_updater,
        TemplateFineGrainedPermissionSaver $template_fine_grained_saver,
        PermissionChangesDetector $permission_changes_detector,
        RegexpFineGrainedEnabler $regexp_enabler,
        RegexpFineGrainedRetriever $regexp_retriever,
        RegexpPermissionFilter $permission_filter,
        RegexpFineGrainedDisabler $regexp_disabler
    ) {
        $this->permissions_manager          = $permissions_manager;
        $this->history_dao                  = $history_dao;
        $this->history_value_formatter      = $history_value_formatter;
        $this->fine_grained_retriever       = $fine_grained_retriever;
        $this->default_fine_grained_factory = $default_fine_grained_factory;
        $this->fine_grained_updater         = $fine_grained_updater;
        $this->template_fine_grained_saver  = $template_fine_grained_saver;
        $this->permission_changes_detector  = $permission_changes_detector;
        $this->regexp_enabler               = $regexp_enabler;
        $this->regexp_retriever             = $regexp_retriever;
        $this->permission_filter            = $permission_filter;
        $this->regexp_disabler              = $regexp_disabler;
    }

    public function updateProjectTemplatePermissions(Codendi_Request $request)
    {
        $project    = $request->getProject();
        $project_id = $project->getID();

        $csrf = new CSRFSynchronizerToken("/plugins/git/?group_id=$project_id&action=admin-default-access-rights");
        $csrf->check();

        $read_ugroup_ids                 = array();
        $write_ugroup_ids                = array();
        $rewind_ugroup_ids               = array();
        $ugroup_ids                      = $request->get(self::REQUEST_KEY);
        $enable_fine_grained_permissions = $request->get('use-fine-grained-permissions');
        $enable_regexp                   = $request->get('use-regexp');

        if ($ugroup_ids) {
            $read_ugroup_ids   = $this->getUgroupIdsForPermission($ugroup_ids, Git::DEFAULT_PERM_READ);
            $write_ugroup_ids  = $this->getUgroupIdsForPermission($ugroup_ids, Git::DEFAULT_PERM_WRITE);
            $rewind_ugroup_ids = $this->getUgroupIdsForPermission($ugroup_ids, Git::DEFAULT_PERM_WPLUS);
        }

        if (
            $this->isDisablingFineGrainedPermissions($project, $enable_fine_grained_permissions)
            && empty($write_ugroup_ids)
        ) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-git', 'Please define Write permissions.')
            );
            return false;
        }

        $are_there_changes = $this->updateTemplateFineGrainedPermissions(
            $project,
            $request,
            $read_ugroup_ids,
            $write_ugroup_ids,
            $rewind_ugroup_ids,
            $enable_fine_grained_permissions,
            $enable_regexp
        );

        if ($are_there_changes) {
            $this->history_dao->groupAddHistory(
                Git::DEFAULT_GIT_PERMS_GRANTED_FOR_PROJECT,
                $this->history_value_formatter->formatValueForProject($project),
                $project_id,
                array($project_id)
            );
        }

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-git', 'Access control template successfully saved.')
        );
    }

    private function updateTemplateFineGrainedPermissions(
        Project $project,
        Codendi_Request $request,
        array $read_ugroup_ids,
        array $write_ugroup_ids,
        array $rewind_ugroup_ids,
        $enable_fine_grained_permissions,
        $enable_regexp
    ) {
        $current_permissions = $this->default_fine_grained_factory->getBranchesFineGrainedPermissionsForProject($project)
            + $this->default_fine_grained_factory->getTagsFineGrainedPermissionsForProject($project);

        $updated_permissions        = array();
        $added_tags_permissions     = array();
        $added_branches_permissions = array();

        if (
            $this->isEnablingFineGrainedPermissions($project, $enable_fine_grained_permissions) &&
            count($current_permissions) === 0
        ) {
            $added_tags_permissions     = $this->default_fine_grained_factory->getDefaultTagsFineGrainedPermissionsForProject($project);
            $added_branches_permissions = $this->default_fine_grained_factory->getDefaultBranchesFineGrainedPermissionsForProject($project);
        } else {
            if (
                $enable_fine_grained_permissions &&
                ! $this->isEnablingFineGrainedPermissions($project, $enable_fine_grained_permissions)
            ) {
                $updated_permissions = $this->default_fine_grained_factory->getUpdatedPermissionsFromRequest(
                    $request,
                    $project
                );
            }

            $added_branches_permissions = $this->default_fine_grained_factory->getBranchesFineGrainedPermissionsFromRequest(
                $request,
                $project
            );

            $added_tags_permissions = $this->default_fine_grained_factory->getTagsFineGrainedPermissionsFromRequest(
                $request,
                $project
            );
        }

        $are_there_changes = $this->permission_changes_detector->areThereChangesInPermissionsForProject(
            $project,
            $read_ugroup_ids,
            $write_ugroup_ids,
            $rewind_ugroup_ids,
            $enable_fine_grained_permissions,
            $added_branches_permissions,
            $added_tags_permissions,
            $updated_permissions
        );

        $this->saveDefaultPermissionIfNotEmpty(
            $project,
            $read_ugroup_ids,
            Git::DEFAULT_PERM_READ
        );

        $this->saveDefaultPermissionIfNotEmpty(
            $project,
            $write_ugroup_ids,
            Git::DEFAULT_PERM_WRITE
        );

        if ($enable_fine_grained_permissions) {
            $this->fine_grained_updater->enableProject($project);

            $this->saveDefaultPermissionIfNotEmpty(
                $project,
                $rewind_ugroup_ids,
                Git::DEFAULT_PERM_WPLUS
            );
        } else {
            $this->fine_grained_updater->disableProject($project);

            $this->saveDefaultPermission(
                $project,
                $rewind_ugroup_ids,
                Git::DEFAULT_PERM_WPLUS
            );
        }

        $regexp_activation = '';
        if ($enable_regexp && $this->regexp_retriever->areRegexpActivatedForDefault($project) === false) {
            $this->regexp_enabler->enableForTemplate($project);
            $regexp_activation = dgettext('tuleap-git', 'enabled');
        } elseif (! $enable_regexp && $this->regexp_retriever->areRegexpActivatedForDefault($project) === true) {
            $this->regexp_disabler->disableForTemplate($project);
            $this->permission_filter->filterNonRegexpPermissionsForDefault($project);
            $regexp_activation = dgettext('tuleap-git', 'disabled');
        }

        foreach ($added_branches_permissions as $added_branch_permission) {
            $this->template_fine_grained_saver->saveBranchPermission($added_branch_permission);
        }

        foreach ($added_tags_permissions as $added_tag_permission) {
            $this->template_fine_grained_saver->saveTagPermission($added_tag_permission);
        }

        foreach ($updated_permissions as $permission) {
            $this->template_fine_grained_saver->updateTemplatePermission($permission);
        }

        if ($regexp_activation !== '') {
            $this->history_dao->groupAddHistory(
                'regexp_activated_for_git_template',
                sprintf(dgettext('tuleap-git', 'Regular expression %1$s for project %2$s.'), $regexp_activation, $project->getPublicName()),
                $project->getID(),
                array($regexp_activation, $project->getUnixNameMixedCase())
            );
        }

        return $are_there_changes;
    }

    private function isEnablingFineGrainedPermissions(Project $project, $enable_fine_grained_permissions)
    {
        return (
            ! $this->fine_grained_retriever->doesProjectUseFineGrainedPermissions($project)
            && $enable_fine_grained_permissions
        );
    }

    /**
     * @return array
     */
    private function getUgroupIdsForPermission(array $ugroup_ids, $permission)
    {
        $ugroup_ids_for_permission = array();

        if (isset($ugroup_ids[$permission]) && is_array($ugroup_ids[$permission])) {
            $ugroup_ids_for_permission = $ugroup_ids[$permission];
        }

        return $ugroup_ids_for_permission;
    }

    private function saveDefaultPermissionIfNotEmpty(Project $project, array $ugroups_ids, $permission)
    {
        if (! empty($ugroups_ids)) {
            $this->saveDefaultPermission($project, $ugroups_ids, $permission);
        }
    }

    private function isDisablingFineGrainedPermissions(Project $project, $enable_fine_grained_permissions)
    {
        return (
            $this->fine_grained_retriever->doesProjectUseFineGrainedPermissions($project)
            && ! $enable_fine_grained_permissions
        );
    }

    private function saveDefaultPermission(Project $project, array $ugroup_ids, $permission)
    {
        $this->permissions_manager->clearPermission($permission, $project->getId());
        $override_collection = $this->permissions_manager->savePermissionsWithoutHistory(
            $project,
            $project->getID(),
            $permission,
            $ugroup_ids
        );

        $override_collection->emitFeedback($permission);
    }
}
