<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * Returns the field fixed in the constructor no matter what artifact is given
 *
 * @see Cardwall_FieldProviders_IProvideFieldGivenAnArtifact
 */
class Cardwall_FieldProviders_CustomFieldRetriever implements Cardwall_FieldProviders_IProvideFieldGivenAnArtifact
{
    /**
     * @var Tracker_FormElement_Field_Selectbox
     */
    private $field;

    public function __construct(Tracker_FormElement_Field_Selectbox $field)
    {
        $this->field = $field;
    }

    /**
     * @return Tracker_FormElement_Field_Selectbox | null
     */
    public function getField(Tracker $tracker)
    {
        return $this->field;
    }
}
