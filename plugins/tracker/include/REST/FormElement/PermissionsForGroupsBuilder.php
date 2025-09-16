<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\REST\FormElement;

use Tracker_FormElement;
use Tuleap\Project\REST\MinimalUserGroupRepresentation;
use Tuleap\Project\UGroupRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\PermissionsFunctionsWrapper;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;

class PermissionsForGroupsBuilder
{
    public function __construct(
        private UGroupRetriever $ugroup_manager,
        private FrozenFieldDetector $read_only_field_detector,
        private PermissionsFunctionsWrapper $permissions_functions_wrapper,
    ) {
    }

    public function getPermissionsForGroups(Tracker_FormElement $form_element, ?Artifact $artifact, \PFUser $user): ?PermissionsForGroupsRepresentation
    {
        $tracker = $form_element->getTracker();
        if (! $tracker) {
            return null;
        }
        if (! $tracker->userIsAdmin($user)) {
            return null;
        }

        $perm_by_group = $this->getPermissions($form_element);
        if (! isset($perm_by_group[$form_element->getId()]['ugroups'])) {
            return null;
        }

        $can_read   = [];
        $can_submit = [];
        $can_update = [];
        foreach ($perm_by_group[$form_element->getId()]['ugroups'] as $ugroup) {
            if (isset($ugroup['permissions'][Tracker_FormElement::PERMISSION_READ])) {
                $this->addUserGroupRepresentationToArray($can_read, $tracker, $ugroup);
            }
            if (isset($ugroup['permissions'][Tracker_FormElement::PERMISSION_SUBMIT])) {
                $this->addUserGroupRepresentationToArray($can_submit, $tracker, $ugroup);
            }
            if (isset($ugroup['permissions'][Tracker_FormElement::PERMISSION_UPDATE])) {
                if ($artifact === null || ($form_element instanceof \Tuleap\Tracker\FormElement\Field\TrackerField && ! $this->read_only_field_detector->isFieldFrozen($artifact, $form_element))) {
                    $this->addUserGroupRepresentationToArray($can_update, $tracker, $ugroup);
                }
            }
        }
        return new PermissionsForGroupsRepresentation($can_read, $can_submit, $can_update);
    }

    private function getPermissions(Tracker_FormElement $form_element): array
    {
        if ($form_element instanceof \Tuleap\Tracker\FormElement\Container\TrackerFormElementContainer) {
            return [];
        }
        if ($form_element instanceof \Tracker_FormElement_StaticField) {
            return [];
        }
        return $this->permissions_functions_wrapper->getFieldUGroupsPermissions($form_element);
    }

    private function addUserGroupRepresentationToArray(array &$ugroups_collection, \Tuleap\Tracker\Tracker $tracker, $result_array): void
    {
        $ugroup = $this->ugroup_manager->getUGroup($tracker->getProject(), $result_array['ugroup']['id']);
        if ($ugroup) {
            $representation       = new MinimalUserGroupRepresentation((int) $ugroup->getProjectId(), $ugroup);
            $ugroups_collection[] = $representation;
        }
    }
}
