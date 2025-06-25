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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Tracker;

/**
 * Represents the fields of a cardwall.
 */
class Cardwall_CardFields // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getFields(Artifact $artifact): array
    {
        $diplayed_fields = [];
        $tracker         = $artifact->getTracker();

        foreach ($this->getDisplayedFields($tracker) as $field) {
            $diplayed_fields[] = $field;
        }

        return $diplayed_fields;
    }

    /**
     * @return Tracker_FormElement_Field[]
     */
    private function getDisplayedFields(Tracker $tracker): array
    {
        return Cardwall_Semantic_CardFields::load($tracker)->getFields();
    }
}
