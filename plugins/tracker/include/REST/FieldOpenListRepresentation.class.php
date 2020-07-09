<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsRepresentation;

/**
 * @psalm-immutable
 */
class Tracker_REST_FormElement_FieldOpenListRepresentation extends Tracker_REST_FormElementRepresentation
{
    /**
     * @var string
     */
    public $hint;

    private function __construct(Tracker_REST_FormElementRepresentation $representation, string $hint)
    {
        foreach (get_object_vars($representation) as $name => $value) {
            $this->$name = $value;
        }

        $this->hint = $hint;
    }

    public static function build(
        Tracker_FormElement $form_element,
        string $type,
        array $permissions,
        ?PermissionsForGroupsRepresentation $permissions_for_groups
    ): Tracker_REST_FormElementRepresentation {
        $representation = parent::build($form_element, $type, $permissions, $permissions_for_groups);
        if (! $form_element instanceof Tracker_FormElement_Field_OpenList) {
            return $representation;
        }

        return new self($representation, $form_element->getProperty('hint'));
    }
}
