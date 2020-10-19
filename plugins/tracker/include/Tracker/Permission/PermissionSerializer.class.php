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

use Tuleap\Project\UGroupLiteralizer;
use Tuleap\Tracker\Artifact\Artifact;

class Tracker_Permission_PermissionsSerializer
{

    /**
     * @var Tracker_Permission_PermissionRetrieveAssignee
     */
    private $assignee_retriever;

    public function __construct(Tracker_Permission_PermissionRetrieveAssignee $assignee_retriever)
    {
        $this->assignee_retriever = $assignee_retriever;
    }

    public function getLiteralizedUserGroupsThatCanViewTracker(Artifact $artifact)
    {
        return $this->literalize(
            $this->getUserGroupsThatCanViewTracker($artifact),
            $artifact->getTracker()->getProject()
        );
    }

    public function getLiteralizedUserGroupsThatCanViewArtifact(Artifact $artifact)
    {
        return $this->literalize(
            $this->getUserGroupsThatCanViewArtifact($artifact),
            $artifact->getTracker()->getProject()
        );
    }

    public function getLiteralizedUserGroupsSubmitterOnly(Artifact $artifact)
    {
        return $this->literalize(
            $this->getUserGroupsSubmitterOnly($artifact),
            $artifact->getTracker()->getProject()
        );
    }

    public function getLiteralizedUserGroupsThatCanViewTrackerFields(Artifact $artifact)
    {
        $u_groups_literalize_by_field = [];
        $u_groups_ids_by_field = $this->getUserGroupsThatCanViewTrackerFields($artifact);
        foreach ($u_groups_ids_by_field as $key => $u_groups_id) {
            $u_groups_literalize_by_field[$key] = $this->literalize(
                $u_groups_id,
                $artifact->getTracker()->getProject()
            );
        }
        return $u_groups_literalize_by_field;
    }

    public function getLiteralizedAllUserGroupsThatCanViewTracker(Tracker $tracker)
    {
        return $this->literalize(
            $this->getAllUserGroupsThatCanViewTracker($tracker),
            $tracker->getProject()
        );
    }

    private function literalize(array $ugroups_ids, Project $project)
    {
        $literalizer = new UGroupLiteralizer();

        return $literalizer->ugroupIdsToString($ugroups_ids, $project);
    }

    public function getUserGroupsThatCanViewTracker(Artifact $artifact)
    {
        $authorized_ugroups  = [ProjectUGroup::PROJECT_ADMIN];
        $tracker_permissions = $artifact->getTracker()->getAuthorizedUgroupsByPermissionType();

        $this->appendAllUGroups($authorized_ugroups, $tracker_permissions, Tracker::PERMISSION_FULL);
        $this->appendAllUGroups($authorized_ugroups, $tracker_permissions, Tracker::PERMISSION_ADMIN);
        $this->appendMatchingUGroups($authorized_ugroups, $tracker_permissions, Tracker::PERMISSION_SUBMITTER, $this->getSubmitterUGroups($artifact));
        $this->appendMatchingUGroups($authorized_ugroups, $tracker_permissions, Tracker::PERMISSION_ASSIGNEE, $this->getAssigneesUGroups($artifact));
        return $authorized_ugroups;
    }

    private function getAllUserGroupsThatCanViewTracker(Tracker $tracker)
    {
        $authorized_ugroups  = [ProjectUGroup::PROJECT_ADMIN];
        $tracker_permissions = $tracker->getAuthorizedUgroupsByPermissionType();

        $this->appendAllUGroups($authorized_ugroups, $tracker_permissions, Tracker::PERMISSION_FULL);
        $this->appendAllUGroups($authorized_ugroups, $tracker_permissions, Tracker::PERMISSION_ADMIN);
        $this->appendAllUGroups($authorized_ugroups, $tracker_permissions, Tracker::PERMISSION_SUBMITTER);
        $this->appendAllUGroups($authorized_ugroups, $tracker_permissions, Tracker::PERMISSION_ASSIGNEE);
        $this->appendAllUGroups($authorized_ugroups, $tracker_permissions, Tracker::PERMISSION_SUBMITTER_ONLY);
        return $authorized_ugroups;
    }

    public function getUserGroupsThatCanViewArtifact(Artifact $artifact)
    {
        $authorized_ugroups  = [];
        $artifact_ugroup_ids = $artifact->getAuthorizedUGroups();

        if ($artifact_ugroup_ids) {
            array_push($authorized_ugroups, ProjectUGroup::PROJECT_ADMIN);
            $authorized_ugroups = array_merge($authorized_ugroups, $artifact_ugroup_ids);
        }

        return array_unique($authorized_ugroups);
    }

    private function getUserGroupsSubmitterOnly(Artifact $artifact)
    {
        $authorized_ugroups  = [];
        $tracker_permissions = $artifact->getTracker()->getAuthorizedUgroupsByPermissionType();
        if (isset($tracker_permissions[Tracker::PERMISSION_SUBMITTER_ONLY])) {
            $authorized_ugroups = $tracker_permissions[Tracker::PERMISSION_SUBMITTER_ONLY];
        }
        return $authorized_ugroups;
    }

    private function getUserGroupsThatCanViewTrackerFields($artifact)
    {
        $authorized_ugroups = [];
        $fields_permissions = $artifact->getTracker()->getFieldsAuthorizedUgroupsByPermissionType();

        foreach ($fields_permissions as $key => $field_permissions) {
            $authorized_ugroups[$key] = [];
            $this->appendAllUGroups($authorized_ugroups[$key], $field_permissions, Tracker_FormElement::PERMISSION_READ);
            $this->appendAllUGroups($authorized_ugroups[$key], $field_permissions, Tracker_FormElement::PERMISSION_UPDATE);
        }

        return $authorized_ugroups;
    }

    private function appendAllUGroups(array &$authorized_ugroups, array $tracker_permissions, $permission_type)
    {
        if (isset($tracker_permissions[$permission_type])) {
            $this->appendToArray(
                $authorized_ugroups,
                $tracker_permissions[$permission_type]
            );
        }
    }

    private function appendMatchingUGroups(array &$authorized_ugroups, array $tracker_permissions, $permission_type, array $ugroup_ids)
    {
        if (isset($tracker_permissions[$permission_type])) {
            $this->appendToArray(
                $authorized_ugroups,
                array_intersect(
                    $tracker_permissions[$permission_type],
                    $ugroup_ids
                )
            );
        }
    }

    private function appendToArray(array &$authorized_ugroups, array $groups)
    {
        $authorized_ugroups = array_merge($authorized_ugroups, $groups);
        return $authorized_ugroups;
    }

    private function getSubmitterUGroups(Artifact $artifact)
    {
        return $this->getUserUGroups($artifact->getSubmittedByUser(), $artifact);
    }

    private function getAssigneesUGroups(Artifact $artifact)
    {
        $assignees_ugroups = [];
        foreach ($this->assignee_retriever->getAssignees($artifact) as $assignee) {
            $assignees_ugroups = array_merge($assignees_ugroups, $this->getUserUGroups($assignee, $artifact));
        }
        return $assignees_ugroups;
    }

    private function getUserUGroups(PFUser $user, Artifact $artifact)
    {
        return $user->getUgroups($artifact->getTracker()->getProject()->getID(), []);
    }
}
