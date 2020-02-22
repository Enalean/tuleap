<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * Represents the fields of a cardwall.
 */
class Cardwall_CardFields
{

    public function __construct(Tracker_FormElementFactory $factory)
    {
        $this->form_element_factory = $factory;
    }

    /**
     *
     *
     * @return Tracker_FormElement_Field[]
     */
    public function getFields(Tracker_Artifact $artifact)
    {
        $diplayed_fields = array();
        $tracker         = $artifact->getTracker();

        foreach ($this->getDisplayedFields($tracker) as $field) {
            $diplayed_fields[] = $field;
        }

        return $diplayed_fields;
    }

    private function getDisplayedFields(Tracker $tracker)
    {
        $semantic = Cardwall_Semantic_CardFields::load($tracker);
        return $semantic->getFields();
    }
}
