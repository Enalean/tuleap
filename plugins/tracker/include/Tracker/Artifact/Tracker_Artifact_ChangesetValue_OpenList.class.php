<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueOpenListFullRepresentation;

/**
 * Manage values in changeset for string fields
 */
class Tracker_Artifact_ChangesetValue_OpenList extends Tracker_Artifact_ChangesetValue_List
{

    /**
     * @return mixed
     */
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
    {
        return $visitor->visitOpenList($this);
    }

    /**
     * Get the value (an array of int)
     *
     * @return array of int The values of this artifact changeset value
     */
    public function getValue()
    {
        $values = $this->getListValues();
        $array = [];
        foreach ($values as $value) {
            $array[] = $value->getJsonId();
        }
        return $array;
    }

    protected function getRESTBindValue(Tracker_FormElement_Field_List_Value $value)
    {
        return $value->getAPIValue();
    }

    public function getFullRESTValue(PFUser $user)
    {
        $full_values = [];
        $labels      = [];
        foreach ($this->getListValues() as $list_value) {
            $full_values[] = $this->getFullRESTBindValue($list_value);
            $labels[]      = $this->getLabel($list_value);
        }

        $representation           = new ArtifactFieldValueOpenListFullRepresentation();
        $representation->build(
            $this->field->getId(),
            Tracker_FormElementFactory::instance()->getType($this->field),
            $this->field->getLabel(),
            $this->field->getBind()->getType(),
            array_values($full_values),
            array_values($labels)
        );
        return $representation;
    }

    protected function getLabel($value)
    {
        return $value->getLabel();
    }
}
