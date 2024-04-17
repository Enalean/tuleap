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

use ForgeConfig;
use LogicException;
use PFUser;
use Tracker_FormElement;
use Tracker_UserWithReadAllPermission;
use Tracker_Workflow_WorkflowUser;
use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\User\TuleapFunctionsUser;

final class TrackersPermissionsRetriever implements RetrieveUserPermissionOnFields
{
    #[FeatureFlagConfigKey('Use the new way of checking user permissions on Trackers')]
    #[ConfigKeyInt(0)]
    #[ConfigKeyHidden]
    public const FEATURE_FLAG = 'new_tracker_permissions_check';

    public function __construct(
        private readonly SearchUserGroupsPermissionOnFields $dao,
    ) {
    }

    public static function isEnabled(): bool
    {
        return (int) ForgeConfig::getFeatureFlag(self::FEATURE_FLAG) === 1;
    }

    public function retrieveUserPermissionOnFields(PFUser $user, array $fields, FieldPermissionType $permission): UserPermissionsOnObjects
    {
        if (! self::isEnabled()) {
            throw new LogicException('Trackers permissions on tracker are disabled by feature flag.');
        }

        if (empty($fields)) {
            return new UserPermissionsOnObjects($user, $permission, [], []);
        }

        if (
            $user instanceof Tracker_Workflow_WorkflowUser
            || $user instanceof TuleapFunctionsUser
            || ($permission === FieldPermissionType::PERMISSION_READ && $user instanceof Tracker_UserWithReadAllPermission)
        ) {
            return new UserPermissionsOnObjects($user, $permission, $fields, []);
        }

        $results = $this->dao->searchUserGroupsPermissionOnFields(
            $this->getUserUGroups($user, $fields),
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

        return new UserPermissionsOnObjects($user, $permission, $allowed, $not_allowed);
    }

    /**
     * @param Tracker_FormElement[] $fields
     * @return int[]
     */
    private function getUserUGroups(PFUser $user, array $fields): array
    {
        $ugroups_id = [];
        foreach ($fields as $field) {
            $project_id = (int) $field->getTracker()->getProject()->getID();
            $ugroups_id = array_merge($ugroups_id, $user->getUgroups($project_id, ['project_id' => $project_id]));
        }

        return array_map(static fn(int|string $id) => (int) $id, $ugroups_id);
    }
}
