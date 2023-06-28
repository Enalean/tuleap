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

namespace Tuleap\Tracker\Tracker\XML\Updater;

use SimpleXMLElement;
use Tracker_FormElement_Field_PermissionsOnArtifact;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\MatchPermissionsByDuckTyping;
use Tuleap\Tracker\XML\Updater\MoveChangesetXMLUpdater;

final class PermissionsByDuckTypingUpdater implements UpdatePermissionsByDuckTyping
{
    public function __construct(
        private readonly MatchPermissionsByDuckTyping $match_permissions_by_duck_typing,
        private readonly MoveChangesetXMLUpdater $XML_updater,
    ) {
    }

    public function updatePermissionsForDuckTypingMove(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field_PermissionsOnArtifact $destination_field,
        int $index,
    ): void {
        $last_index = count($changeset_xml->field_change[$index]->value) - 1;

        for ($value_index = $last_index; $value_index >= 0; $value_index--) {
            $does_user_group_exists_in_destination_field = $this->match_permissions_by_duck_typing->doesUserGroupExistsInDestinationField(
                $destination_field,
                (string) $changeset_xml->field_change[$index]->value[$value_index]
            );

            if ($does_user_group_exists_in_destination_field) {
                continue;
            }

            $this->XML_updater->deleteValueInFieldChangeAtIndex($changeset_xml, $index, $value_index);
        }
    }
}
