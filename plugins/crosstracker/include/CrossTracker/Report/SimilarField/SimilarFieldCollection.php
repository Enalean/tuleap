<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\SimilarField;

use Tracker_Artifact;
use Tracker_FormElement_Field;

class SimilarFieldCollection
{
    /** @var int[] */
    private $similar_fields;

    /**
     * @param Tracker_FormElement_Field[] $similar_fields
     */
    public function __construct(array $similar_fields)
    {
        $this->similar_fields = $similar_fields;
    }

    /**
     * @param Tracker_FormElement_Field $field
     * @param int                       $tracker_id
     */
    public function addField(Tracker_FormElement_Field $field, $tracker_id)
    {
        $this->similar_fields[$field->getName()][$tracker_id] = $field;
    }

    /**
     * @return string[]
     */
    public function getFieldNames()
    {
        return array_keys($this->similar_fields);
    }

    /**
     * @param Tracker_Artifact $artifact
     * @param string           $field_name
     * @return Tracker_FormElement_Field|null
     */
    public function getField(Tracker_Artifact $artifact, $field_name)
    {
        if (! isset($this->similar_fields[$field_name][$artifact->getTrackerId()])) {
            return null;
        }
        return $this->similar_fields[$field_name][$artifact->getTrackerId()];
    }
}
