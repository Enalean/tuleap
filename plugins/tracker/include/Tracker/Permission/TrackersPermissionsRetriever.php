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
use ForgeConfig;
use LogicException;
use PFUser;
use Project;
use Project_AccessException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker;
use Tracker_FormElement;
use Tracker_UserWithReadAllPermission;
use Tracker_Workflow_WorkflowUser;
use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\include\CheckUserCanAccessProject;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\User\TuleapFunctionsUser;
use URLVerification;

final readonly class TrackersPermissionsRetriever implements RetrieveUserPermissionOnFields, RetrieveUserPermissionOnTrackers
{
    #[FeatureFlagConfigKey('Use the new way of checking user permissions on Trackers')]
    #[ConfigKeyInt(0)]
    #[ConfigKeyHidden]
    public const FEATURE_FLAG = 'new_tracker_permissions_check';

    public function __construct(
        private SearchUserGroupsPermissionOnFields $fields_dao,
        private SearchUserGroupsPermissionOnTrackers $trackers_dao,
        private CheckUserCanAccessProject $project_access,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public static function build(): self
    {
        $dao = new TrackersPermissionsDao();

        return new self($dao, $dao, new URLVerification(), EventManager::instance());
    }

    public static function isEnabled(): bool
    {
        return (int) ForgeConfig::getFeatureFlag(self::FEATURE_FLAG) === 1;
    }

    public function retrieveUserPermissionOnFields(PFUser $user, array $fields, FieldPermissionType $permission): UserPermissionsOnItems
    {
        if (! self::isEnabled()) {
            throw new LogicException('Trackers permissions on tracker are disabled by feature flag.');
        }

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

        $results = $this->fields_dao->searchUserGroupsPermissionOnFields(
            $this->getUserUGroupsFromFields($user, $fields),
            array_map(static fn(Tracker_FormElement $element) => $element->getId(), $fields),
            $permission->value
        );

        $allowed     = [];
        $not_allowed = [];
        foreach ($fields as $field) {
            if (in_array($field->getId(), $results)) {
                $allowed[] = $field;
            } else {
                $not_allowed[] = $field;
            }
        }

        return new UserPermissionsOnItems($user, $permission, $allowed, $not_allowed);
    }

    public function retrieveUserPermissionOnTrackers(PFUser $user, array $trackers, TrackerPermissionType $permission): UserPermissionsOnItems
    {
        if (! self::isEnabled()) {
            throw new LogicException('Trackers permissions on tracker are disabled by feature flag.');
        }

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
            $this->getUserUGroupsFromTrackers($user, $trackers),
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

    /**
     * @param Tracker_FormElement[] $fields
     * @return int[]
     */
    private function getUserUGroupsFromFields(PFUser $user, array $fields): array
    {
        $ugroups_id = [];
        foreach ($fields as $field) {
            $project_id = (int) $field->getTracker()->getProject()->getID();
            $ugroups_id = array_merge($ugroups_id, $user->getUgroups($project_id, ['project_id' => $project_id]));
        }

        return array_map(static fn(int|string $id) => (int) $id, $ugroups_id);
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

    private function userCanAccessProject(PFUser $user, Project $project): bool
    {
        try {
            return $this->project_access->userCanAccessProject($user, $project);
        } catch (Project_AccessException) {
            return false;
        }
    }
}
