<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\Permission;

use PFUser;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Permission\ArtifactPermissionType;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnArtifacts;
use Tuleap\Tracker\Permission\UserPermissionsOnItems;

final class RetrieveUserPermissionOnArtifactsStub implements RetrieveUserPermissionOnArtifacts
{
    /**
     * @var array<string, list<int>>
     */
    private array $has_permission_on = [];

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    /**
     * @param list<int> $tracker_ids
     */
    public function withPermissionOn(array $tracker_ids, ArtifactPermissionType $permission): self
    {
        $this->has_permission_on[$permission->name] = $tracker_ids;

        return $this;
    }

    #[\Override]
    public function retrieveUserPermissionOnArtifacts(PFUser $user, array $artifacts, ArtifactPermissionType $permission): UserPermissionsOnItems
    {
        if (isset($this->has_permission_on[$permission->name])) {
            return new UserPermissionsOnItems(
                $user,
                $permission,
                array_filter($artifacts, fn(Artifact $artifact) => in_array($artifact->getId(), $this->has_permission_on[$permission->name], true)),
                array_filter($artifacts, fn(Artifact $artifact) => ! in_array($artifact->getId(), $this->has_permission_on[$permission->name], true)),
            );
        }

        return new UserPermissionsOnItems($user, $permission, [], $artifacts);
    }
}
