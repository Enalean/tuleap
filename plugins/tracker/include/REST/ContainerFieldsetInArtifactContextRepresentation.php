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
 */

namespace Tuleap\Tracker\REST;

use Tracker_FormElement_Container_Fieldset;
use Tracker_REST_FormElementRepresentation;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsRepresentation;

/**
 * @psalm-immutable
 */
class ContainerFieldsetInArtifactContextRepresentation extends Tracker_REST_FormElementRepresentation
{
    /**
     * @var bool
     */
    public $is_hidden = false;

    private function __construct(Tracker_REST_FormElementRepresentation $representation, bool $is_hidden)
    {
        foreach (get_object_vars($representation) as $name => $value) {
            $this->$name = $value;
        }
        $this->is_hidden = $is_hidden;
    }

    public static function buildContainerFieldset(
        Tracker_FormElement_Container_Fieldset $form_element,
        string $type,
        array $permissions,
        ?PermissionsForGroupsRepresentation $permissions_for_groups,
        bool $is_hidden
    ): self {
        return new self(
            parent::build($form_element, $type, $permissions, $permissions_for_groups),
            $is_hidden
        );
    }
}
