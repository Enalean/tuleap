<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField;

final class CanPermissionsBeFullyMovedVerifier implements VerifyPermissionsCanBeFullyMoved
{
    #[\Override]
    public function canAllPermissionsBeFullyMoved(
        PermissionsOnArtifactField $source_field,
        PermissionsOnArtifactField $destination_field,
        Artifact $artifact,
        LoggerInterface $logger,
    ): bool {
        $last_changeset_value = $source_field->getLastChangesetValue($artifact);
        if (! $last_changeset_value instanceof \Tracker_Artifact_ChangesetValue_PermissionsOnArtifact) {
            $logger->debug(sprintf('Last changeset value is not a permission on artifact for field #%d (%s)', $source_field->getId(), $source_field->getName()));
            return false;
        }

        $selected_user_groups    = $last_changeset_value->getUgroupNamesFromPerms();
        $destination_user_groups = array_map(static fn($user_group) => $user_group->getName(), $destination_field->getAllUserGroups());

        foreach ($selected_user_groups as $selected_user_group) {
            if (! in_array($selected_user_group, $destination_user_groups, true)) {
                $logger->debug(sprintf('User group %s is not a possible value of field #%d (%s)', $selected_user_group, $destination_field->getId(), $destination_field->getName()));
                return false;
            }
        }

        return true;
    }
}
