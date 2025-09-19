<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Permission;

use EventManager;
use PFUser;
use Project;
use Project_AccessException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker_Permission_PermissionRetrieveAssignee;
use Tracker_UserWithReadAllPermission;
use Tracker_Workflow_WorkflowUser;
use Tuleap\Project\CachedProjectAccessChecker;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\FormElement\TrackerFormElement;
use Tuleap\Tracker\Tracker;
use Tuleap\User\RetrieveUserById;
use Tuleap\User\TuleapFunctionsUser;
use UserManager;

final readonly class TrackersPermissionsRetriever implements RetrieveUserPermissionOnFields, RetrieveUserPermissionOnTrackers, RetrieveUserPermissionOnArtifacts
{
    public function __construct(
        private SearchUserGroupsPermissionOnFields $fields_dao,
        private SearchUserGroupsPermissionOnTrackers $trackers_dao,
        private SearchUserGroupsPermissionOnArtifacts $artifacts_dao,
        private CheckProjectAccess $project_access,
        private EventDispatcherInterface $dispatcher,
        private RetrieveUserById $user_manager,
    ) {
    }

    public static function build(): self
    {
        $dao              = new TrackersPermissionsDao();
        $event_dispatcher = EventManager::instance();

        return new self(
            $dao,
            $dao,
            $dao,
            new CachedProjectAccessChecker(new ProjectAccessChecker(new RestrictedUserCanAccessProjectVerifier(), $event_dispatcher)),
            $event_dispatcher,
            UserManager::instance()
        );
    }

    #[\Override]
    public function retrieveUserPermissionOnFields(PFUser $user, array $fields, FieldPermissionType $permission): UserPermissionsOnItems
    {
        if ($fields === []) {
            return new UserPermissionsOnItems($user, $permission, [], []);
        }

        if (
            $user instanceof Tracker_Workflow_WorkflowUser
            || $user instanceof TuleapFunctionsUser
            || ($permission === FieldPermissionType::PERMISSION_READ && $user instanceof Tracker_UserWithReadAllPermission)
        ) {
            return new UserPermissionsOnItems($user, $permission, $fields, []);
        }

        $user_groups_id = $this->getUserUGroupsFromFields($user, $fields);
        $results        = $this->fields_dao->searchUserGroupsPermissionOnFields(
            $user_groups_id,
            array_map(static fn(TrackerFormElement $element) => $element->getId(), $fields),
            $permission
        );
        if ($permission === FieldPermissionType::PERMISSION_READ) {
            // If user has permission update, it has also permission read
            $results = array_merge(
                $results,
                $this->fields_dao->searchUserGroupsPermissionOnFields(
                    $user_groups_id,
                    array_map(static fn(TrackerFormElement $element) => $element->getId(), $fields),
                    FieldPermissionType::PERMISSION_UPDATE,
                ),
            );
        }

        $allowed     = [];
        $not_allowed = [];
        foreach ($fields as $field) {
            if (in_array($field->getId(), $results, true)) {
                $allowed[] = $field;
            } else {
                $not_allowed[] = $field;
            }
        }

        return new UserPermissionsOnItems($user, $permission, $allowed, $not_allowed);
    }

    #[\Override]
    public function retrieveUserPermissionOnTrackers(PFUser $user, array $trackers, TrackerPermissionType $permission): UserPermissionsOnItems
    {
        if ($trackers === []) {
            return new UserPermissionsOnItems($user, $permission, [], []);
        }

        return match ($permission) {
            TrackerPermissionType::PERMISSION_VIEW   => $this->buildTrackerViewPermissions($user, $trackers),
            TrackerPermissionType::PERMISSION_SUBMIT => $this->buildTrackerSubmitPermissions($user, $trackers),
        };
    }

    /**
     * @param Tracker[] $trackers
     * @return UserPermissionsOnItems<Tracker, TrackerPermissionType>
     */
    private function buildTrackerViewPermissions(PFUser $user, array $trackers): UserPermissionsOnItems
    {
        $results = $this->trackers_dao->searchUserGroupsViewPermissionOnTrackers(
            $this->getUserUGroupsWithProjectFromTrackers($user, $trackers),
            array_map(static fn(Tracker $tracker) => $tracker->getId(), $trackers)
        );

        $allowed     = [];
        $not_allowed = [];
        foreach ($trackers as $tracker) {
            if (
                (in_array($tracker->getId(), $results, true) || $tracker->userIsAdmin($user))
                && $this->userCanAccessProject($user, $tracker->getProject())
            ) {
                $allowed[] = $tracker;
            } else {
                $not_allowed[] = $tracker;
            }
        }

        return new UserPermissionsOnItems($user, TrackerPermissionType::PERMISSION_VIEW, $allowed, $not_allowed);
    }

    /**
     * @param Tracker[] $trackers
     * @return UserPermissionsOnItems<Tracker, TrackerPermissionType>
     */
    private function buildTrackerSubmitPermissions(PFUser $user, array $trackers): UserPermissionsOnItems
    {
        if ($user->isAnonymous()) {
            return new UserPermissionsOnItems($user, TrackerPermissionType::PERMISSION_SUBMIT, [], $trackers);
        }

        $results = $this->trackers_dao->searchUserGroupsSubmitPermissionOnTrackers(
            $this->getUserUGroupsFromTrackers($user, $trackers),
            array_map(static fn(Tracker $tracker) => $tracker->getId(), $trackers)
        );

        $allowed     = [];
        $not_allowed = [];
        foreach ($trackers as $tracker) {
            if ($this->canUserSubmitArtifactFromTracker($user, $tracker, $results)) {
                $allowed[] = $tracker;
            } else {
                $not_allowed[] = $tracker;
            }
        }

        return new UserPermissionsOnItems($user, TrackerPermissionType::PERMISSION_SUBMIT, $allowed, $not_allowed);
    }

    /**
     * @param int[] $allowed_trackers
     */
    private function canUserSubmitArtifactFromTracker(PFUser $user, Tracker $tracker, array $allowed_trackers): bool
    {
        $project_access = $this->userCanAccessProject($user, $tracker->getProject());
        if (
            in_array($tracker->getId(), $allowed_trackers, true)
            && $this->dispatcher->dispatch(new CanSubmitNewArtifact($user, $tracker))->canSubmitNewArtifact()
            && $project_access
        ) {
            return true;
        }

        if ($tracker->userIsAdmin($user) && $project_access) {
            return true;
        }

        return false;
    }

    #[\Override]
    public function retrieveUserPermissionOnArtifacts(PFUser $user, array $artifacts, ArtifactPermissionType $permission): UserPermissionsOnItems
    {
        if ($artifacts === []) {
            return new UserPermissionsOnItems($user, $permission, [], []);
        }

        if ($permission === ArtifactPermissionType::PERMISSION_UPDATE && $user->isAnonymous()) {
            return new UserPermissionsOnItems($user, $permission, [], $artifacts);
        }

        $results = $this->artifacts_dao->searchUserGroupsViewPermissionOnArtifacts(
            $this->getUserUGroupsFromArtifacts($user, $artifacts),
            array_map(static fn(Artifact $artifact) => $artifact->getId(), $artifacts)
        );

        $allowed     = [];
        $not_allowed = [];
        foreach ($artifacts as $artifact) {
            if (
                (in_array($artifact->getId(), $results) && $this->userHavePermissionOnTracker($user, $artifact))
                || $artifact->getTracker()->userIsAdmin($user)
            ) {
                $allowed[] = $artifact;
            } else {
                $not_allowed[] = $artifact;
            }
        }

        return new UserPermissionsOnItems($user, $permission, $allowed, $not_allowed);
    }

    /**
     * @param TrackerFormElement[] $fields
     * @return list<UserGroupInProject>
     */
    private function getUserUGroupsFromFields(PFUser $user, array $fields): array
    {
        $result = [];
        foreach ($fields as $field) {
            $project_id = (int) $field->getTracker()->getProject()->getID();
            $ugroups    = $user->getUgroups($project_id, ['project_id' => $project_id]);
            foreach ($ugroups as $ugroup) {
                $result[] = new UserGroupInProject($project_id, (int) $ugroup);
            }
        }

        return array_values(array_unique($result, SORT_REGULAR));
    }

    /**
     * @param Tracker[] $trackers
     * @return list<UserGroupInProject>
     */
    private function getUserUGroupsWithProjectFromTrackers(PFUser $user, array $trackers): array
    {
        $result = [];
        foreach ($trackers as $tracker) {
            $project_id = (int) $tracker->getProject()->getID();
            $ugroups    = $user->getUgroups($project_id, ['project_id' => $project_id]);
            foreach ($ugroups as $ugroup) {
                $result[] = new UserGroupInProject($project_id, (int) $ugroup);
            }
        }

        return array_values(array_unique($result, SORT_REGULAR));
    }

    /**
     * @param Tracker[] $trackers
     * @return int[]
     */
    private function getUserUGroupsFromTrackers(PFUser $user, array $trackers): array
    {
        $ugroups_id = [];
        foreach ($trackers as $tracker) {
            $project_id = (int) $tracker->getProject()->getID();
            $ugroups_id = array_merge($ugroups_id, $user->getUgroups($project_id, ['project_id' => $project_id]));
        }

        return array_map(static fn(int|string $id) => (int) $id, $ugroups_id);
    }

    /**
     * @param Artifact[] $artifacts
     * @return int[]
     */
    private function getUserUGroupsFromArtifacts(PFUser $user, array $artifacts): array
    {
        $ugroups_id = [];
        foreach ($artifacts as $artifact) {
            $project_id = (int) $artifact->getTracker()->getProject()->getID();
            $ugroups_id = array_merge($ugroups_id, $user->getUgroups($project_id, ['project_id' => $project_id]));
        }

        return array_map(static fn(int|string $id) => (int) $id, $ugroups_id);
    }

    private function userCanAccessProject(PFUser $user, Project $project): bool
    {
        try {
            $this->project_access->checkUserCanAccessProject($user, $project);
            return true;
        } catch (Project_AccessException) {
            return false;
        }
    }

    private function userHavePermissionOnTracker(PFUser $user, Artifact $artifact): bool
    {
        $tracker     = $artifact->getTracker();
        $permissions = $tracker->getAuthorizedUgroupsByPermissionType();

        foreach ($permissions as $permission_type => $ugroups) {
            switch ($permission_type) {
                case Tracker::PERMISSION_FULL:
                    foreach ($ugroups as $ugroup) {
                        if ($user->isMemberOfUGroup($ugroup, (int) $tracker->getGroupId())) {
                            return true;
                        }
                    }
                    break;

                case Tracker::PERMISSION_SUBMITTER:
                    foreach ($ugroups as $ugroup) {
                        if ($user->isMemberOfUGroup($ugroup, (int) $tracker->getGroupId())) {
                            // check that submitter is also a member
                            $submitter = $artifact->getSubmittedByUser();
                            if ($submitter->isMemberOfUGroup($ugroup, (int) $tracker->getGroupId())) {
                                return true;
                            }
                        }
                    }
                    break;

                case Tracker::PERMISSION_ASSIGNEE:
                    foreach ($ugroups as $ugroup) {
                        if ($user->isMemberOfUGroup($ugroup, (int) $tracker->getGroupId())) {
                            // check that one of the assignees is also a member
                            $permission_assignee = new Tracker_Permission_PermissionRetrieveAssignee($this->user_manager);
                            foreach ($permission_assignee->getAssignees($artifact) as $assignee) {
                                if ($assignee->isMemberOfUGroup($ugroup, (int) $tracker->getGroupId())) {
                                    return true;
                                }
                            }
                        }
                    }
                    break;

                case Tracker::PERMISSION_SUBMITTER_ONLY:
                    foreach ($ugroups as $ugroup) {
                        if (
                            $user->isMemberOfUGroup($ugroup, (int) $tracker->getGroupId())
                            && (int) $user->getId() === $artifact->getSubmittedBy()
                        ) {
                            return true;
                        }
                    }
                    break;
            }
        }

        return false;
    }
}
