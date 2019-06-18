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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\Permissions;

use Project;
use UGroupManager;
use Codendi_Request;
use ProjectUGroup;
use PermissionsNormalizer;
use PermissionsNormalizerOverrideCollection;
use PermissionsManager;
use Git;
use Feedback;

class DefaultFineGrainedPermissionFactory
{

    /**
     * @var FineGrainedPermissionSorter
     */
    private $sorter;

    /**
     * @var PatternValidator
     */
    private $validator;

    public const ADD_BRANCH_PREFIX  = 'add-branch';
    public const ADD_TAG_PREFIX     = 'add-tag';
    public const EDIT_BRANCH_PREFIX = 'edit-branch';
    public const EDIT_TAG_PREFIX    = 'edit-tag';

    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    /**
     * @var PermissionsNormalizer
     */
    private $normalizer;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var FineGrainedDao
     */
    private $dao;

    public function __construct(
        FineGrainedDao $dao,
        UGroupManager $ugroup_manager,
        PermissionsNormalizer $normalizer,
        PermissionsManager $permissions_manager,
        PatternValidator $validator,
        FineGrainedPermissionSorter $sorter
    ) {
        $this->dao                 = $dao;
        $this->ugroup_manager      = $ugroup_manager;
        $this->normalizer          = $normalizer;
        $this->permissions_manager = $permissions_manager;
        $this->validator           = $validator;
        $this->sorter              = $sorter;
    }

    public function getUpdatedPermissionsFromRequest(Codendi_Request $request, Project $project)
    {
        $updated_permissions = array();

        $this->updateWriters($request, $project, $updated_permissions);
        $this->updateRewinders($request, $project, $updated_permissions);

        return $updated_permissions;
    }

    private function getAllWriters(Codendi_Request $request)
    {
        $branches = $request->get(self::EDIT_BRANCH_PREFIX . "-write");
        if (! is_array($branches)) {
            $branches = array();
        }

        $tags = $request->get(self::EDIT_TAG_PREFIX . "-write");
        if (! is_array($tags)) {
            $tags = array();
        }

        return $branches + $tags;
    }

    private function updateWriters(Codendi_Request $request, Project $project, array &$updated_permissions)
    {
        $all_writers     = $this->getAllWriters($request);
        $all_permissions = $this->getBranchesFineGrainedPermissionsForProject($project) +
            $this->getTagsFineGrainedPermissionsForProject($project);

        $remaining_permissions = $this->setWritersForPermissionsInRequest(
            $request,
            $all_permissions,
            $all_writers,
            $updated_permissions
        );

        $this->setEmptyWritersForPermissionsNotInRequest(
            $request,
            $remaining_permissions,
            $all_writers,
            $updated_permissions
        );
    }

    private function setWritersForPermissionsInRequest(
        Codendi_Request $request,
        array $all_permissions,
        array $all_writers,
        array &$updated_permissions
    ) {
        foreach ($all_writers as $permission_id => $writers) {
            $permission = $all_permissions[$permission_id];
            unset($all_permissions[$permission_id]);

            if (! $permission || ! $this->hasChangesInWriters($permission, $writers)) {
                continue;
            }

            if (! isset($updated_permissions[$permission_id])) {
                $updated_permissions[$permission_id] = $permission;
            }

            $updated_permissions[$permission_id]->setWriters(
                $this->buildUgroups($request->getProject(), $all_writers, $permission_id)
            );
        }

        return $all_permissions;
    }

    private function setEmptyWritersForPermissionsNotInRequest(
        Codendi_Request $request,
        array $remaining_permissions,
        array $all_writers,
        array &$updated_permissions
    ) {
        foreach ($remaining_permissions as $permission_id => $permission) {
            if (! $this->hasChangesInWriters($permission, array())) {
                continue;
            }

            if (! isset($updated_permissions[$permission_id])) {
                $updated_permissions[$permission_id] = $permission;
            }

            $updated_permissions[$permission_id]->setWriters(
                $this->buildUgroups($request->getProject(), $all_writers, $permission_id)
            );
        }
    }

    private function getAllRewinders(Codendi_Request $request)
    {
        $branches = $request->get(self::EDIT_BRANCH_PREFIX . "-rewind");
        if (! is_array($branches)) {
            $branches = array();
        }

        $tags = $request->get(self::EDIT_TAG_PREFIX . "-rewind");
        if (! is_array($tags)) {
            $tags = array();
        }

        return $branches + $tags;
    }

    private function updateRewinders(Codendi_Request $request, Project $project, array &$updated_permissions)
    {
        $all_rewinders   = $this->getAllRewinders($request);
        $all_permissions = $this->getBranchesFineGrainedPermissionsForProject($project) +
            $this->getTagsFineGrainedPermissionsForProject($project);

        $remaining_permissions = $this->setRewindersForPermissionsInRequest(
            $request,
            $all_permissions,
            $all_rewinders,
            $updated_permissions
        );

        $this->setEmptyRewindersForPermissionsNotInRequest(
            $request,
            $remaining_permissions,
            $all_rewinders,
            $updated_permissions
        );
    }

    private function setRewindersForPermissionsInRequest(
        Codendi_Request $request,
        array $all_permissions,
        array $all_rewinders,
        array &$updated_permissions
    ) {
        foreach ($all_rewinders as $permission_id => $rewinders) {
            $permission = $all_permissions[$permission_id];
            unset($all_permissions[$permission_id]);

            if (! $permission || ! $this->hasChangesInRewinders($permission, $rewinders)) {
                continue;
            }

            if (! isset($updated_permissions[$permission_id])) {
                $updated_permissions[$permission_id] = $permission;
            }

            $updated_permissions[$permission_id]->setRewinders(
                $this->buildUgroups($request->getProject(), $all_rewinders, $permission_id)
            );
        }

        return $all_permissions;
    }

    private function setEmptyRewindersForPermissionsNotInRequest(
        Codendi_Request $request,
        array $remaining_permissions,
        array $all_rewinders,
        array &$updated_permissions
    ) {
        foreach ($remaining_permissions as $permission_id => $permission) {
            if (! $this->hasChangesInRewinders($permission, array())) {
                continue;
            }

            if (! isset($updated_permissions[$permission_id])) {
                $updated_permissions[$permission_id] = $permission;
            }

            $updated_permissions[$permission_id]->setRewinders(
                $this->buildUgroups($request->getProject(), $all_rewinders, $permission_id)
            );
        }
    }

    private function hasChangesInWriters(DefaultFineGrainedPermission $permission, array $ugroup_ids)
    {
        $current_ugroup_ids = array();
        foreach ($permission->getWritersUgroup() as $writer) {
            $current_ugroup_ids[] = $writer->getId();
        }

        return $this->hasChanges($current_ugroup_ids, $ugroup_ids);
    }

    private function hasChangesInRewinders(DefaultFineGrainedPermission $permission, array $ugroup_ids)
    {
        $current_ugroup_ids = array();
        foreach ($permission->getRewindersUgroup() as $rewinder) {
            $current_ugroup_ids[] = $rewinder->getId();
        }

        return $this->hasChanges($current_ugroup_ids, $ugroup_ids);
    }

    private function hasChanges(array $current_ugroup_ids, array $ugroup_ids)
    {
        return (bool) array_diff($current_ugroup_ids, $ugroup_ids) ||
               array_diff($ugroup_ids, $current_ugroup_ids);
    }

    public function getBranchesFineGrainedPermissionsFromRequest(Codendi_Request $request, Project $project)
    {
        return $this->buildRepresentationFromRequest($request, $project, self::ADD_BRANCH_PREFIX);
    }

    public function getTagsFineGrainedPermissionsFromRequest(Codendi_Request $request, Project $project)
    {
        return $this->buildRepresentationFromRequest($request, $project, self::ADD_TAG_PREFIX);
    }

    private function buildRepresentationFromRequest(Codendi_Request $request, Project $project, $prefix)
    {
        $permissions              = array();
        $patterns                 = $request->get("$prefix-name");
        $are_we_activating_regexp = $request->get("use-regexp");

        if ($patterns) {
            foreach ($patterns as $index => $pattern) {
                if (! $this->validator->isValidForDefault($project, $pattern, $are_we_activating_regexp)) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::WARN,
                        sprintf(dgettext('tuleap-git', 'The pattern %1$s is not well formed. Skipping.'), $pattern)
                    );

                    continue;
                }

                $writers   = $this->getWritersFromRequest($request, $index, $prefix);
                $rewinders = $this->getRewindersFromRequest($request, $index, $prefix);

                $permissions[] = new DefaultFineGrainedPermission(
                    0,
                    $project->getID(),
                    $pattern,
                    $writers,
                    $rewinders
                );
            }
        }

        return $permissions;
    }

    private function getWritersFromRequest(Codendi_Request $request, $index, $prefix)
    {
        $all_ugroup_ids = $request->get("$prefix-write") ? $request->get("$prefix-write") : array();

        return $this->buildUgroups($request->getProject(), $all_ugroup_ids, $index);
    }

    private function getRewindersFromRequest(Codendi_Request $request, $index, $prefix)
    {
        $all_ugroup_ids = $request->get("$prefix-rewind") ? $request->get("$prefix-rewind") : array();

        return $this->buildUgroups($request->getProject(), $all_ugroup_ids, $index);
    }

    /**
     * @return array
     */
    private function buildUgroups(Project $project, array $all_ugroup_ids, $index)
    {
        $ugroups    = array();
        $collection = new PermissionsNormalizerOverrideCollection();

        if (isset($all_ugroup_ids[$index])) {
            $normalized_ugroup_ids = $this->normalizer->getNormalizedUGroupIds(
                $project,
                $all_ugroup_ids[$index],
                $collection
            );

            foreach ($normalized_ugroup_ids as $ugroup_id) {
                $ugroups[] = $this->ugroup_manager->getById($ugroup_id);
            }
        }

        $collection->emitFeedback('');
        return $ugroups;
    }

    public function getBranchesFineGrainedPermissionsForProject(Project $project)
    {
        $permissions = array();

        foreach ($this->dao->searchDefaultBranchesFineGrainedPermissions($project->getID()) as $row) {
            $permission    = $this->getInstanceFromRow($row);
            $permissions[$permission->getId()] = $permission;
        }

        return $this->sorter->sort($permissions);
    }

    public function getTagsFineGrainedPermissionsForProject(Project $project)
    {
        $permissions = array();

        foreach ($this->dao->searchDefaultTagsFineGrainedPermissions($project->getID()) as $row) {
            $permission    = $this->getInstanceFromRow($row);
            $permissions[$permission->getId()] = $permission;
        }

        return $this->sorter->sort($permissions);
    }

    /**
     * @return UGroups[]
     */
    private function getWritersForPermission($permission_id)
    {
        $ugroups = array();

        foreach ($this->dao->searchDefaultWriterUgroupIdsForFineGrainedPermissions($permission_id) as $row) {
            $ugroups[] = $this->ugroup_manager->getById($row['ugroup_id']);
        }

        return $ugroups;
    }

    /**
     * @return UGroups[]
     */
    private function getRewindersForPermission($permission_id)
    {
        $ugroups = array();

        foreach ($this->dao->searchDefaultRewinderUgroupIdsForFineGrainePermissions($permission_id) as $row) {
            $ugroups[] = $this->ugroup_manager->getById($row['ugroup_id']);
        }

        return $ugroups;
    }

    public function mapBranchPermissionsForProject(
        Project $template_project,
        $new_project_id,
        array $ugroups_mapping
    ) {
        $permissions = $this->getBranchesFineGrainedPermissionsForProject($template_project);

        return $this->mapFineGrainedPermissions(
            $new_project_id,
            $permissions,
            $ugroups_mapping
        );
    }

    public function mapTagPermissionsForProject(
        Project $template_project,
        $new_project_id,
        array $ugroups_mapping
    ) {
        $permissions = $this->getTagsFineGrainedPermissionsForProject($template_project);

        return $this->mapFineGrainedPermissions(
            $new_project_id,
            $permissions,
            $ugroups_mapping
        );
    }

    private function mapFineGrainedPermissions(
        $new_project_id,
        array $permissions,
        array $ugroups_mapping
    ) {
        $new_permissions = array();

        foreach ($permissions as $permission) {
            $writers   = $this->mapUgroup($permission->getWritersUgroup(), $ugroups_mapping);
            $rewinders = $this->mapUgroup($permission->getRewindersUgroup(), $ugroups_mapping);

            $new_permissions[] = new DefaultFineGrainedPermission(
                0,
                $new_project_id,
                $permission->getPatternWithoutPrefix(),
                $writers,
                $rewinders
            );
        }

        return $new_permissions;
    }

    private function mapUgroup(array $ugroups, array $ugroups_mapping)
    {
        $new_ugroups = array();
        foreach ($ugroups as $ugroup) {
            $new_ugroups[] = $this->getUgroupFromMapping($ugroup, $ugroups_mapping);
        }

        return $new_ugroups;
    }

    private function getUgroupFromMapping(ProjectUGroup $ugroup, array $ugroups_mapping)
    {
        if (! $ugroup->isStatic()) {
            return $ugroup;
        }

        $new_ugroup_id = $ugroups_mapping[$ugroup->getId()];
        return $this->ugroup_manager->getById($new_ugroup_id);
    }

    private function getInstanceFromRow(array $row)
    {
        $permission_id = $row['id'];

        return new DefaultFineGrainedPermission(
            $permission_id,
            $row['project_id'],
            $row['pattern'],
            $this->getWritersForPermission($permission_id),
            $this->getRewindersForPermission($permission_id)
        );
    }

    public function getDefaultBranchesFineGrainedPermissionsForProject(Project $project)
    {
        return array(
            $this->buildDefaultForProject($project)
        );
    }

    public function getDefaultTagsFineGrainedPermissionsForProject(Project $project)
    {
        return array(
            $this->buildDefaultForProject($project)
        );
    }

    private function buildDefaultForProject(Project $project)
    {
        $writers = array();
        foreach ($this->permissions_manager->getAuthorizedUgroupIds($project->getID(), Git::DEFAULT_PERM_WRITE) as $id) {
            $writers[] = $this->ugroup_manager->getById($id);
        }

        $rewinders = array();
        foreach ($this->permissions_manager->getAuthorizedUgroupIds($project->getID(), Git::DEFAULT_PERM_WPLUS) as $id) {
            $rewinders[] = $this->ugroup_manager->getById($id);
        }

        return new DefaultFineGrainedPermission(
            0,
            $project->getID(),
            '*',
            $writers,
            $rewinders
        );
    }
}
