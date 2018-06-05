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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use Tracker_Artifact;
use Tracker_FormElement_Field_List;

class BindDecoratorColorRetriever
{
    /**
     * @param Tracker_FormElement_Field_List $field
     * @param Tracker_Artifact $artifact
     * @return string
     */
    public function getCurrentDecoratorColor(Tracker_FormElement_Field_List $field, Tracker_Artifact $artifact)
    {
        $changeset = $artifact->getLastChangeset();
        if (! $changeset) {
            return '';
        };

        $values = $field->getBind()->getChangesetValues($changeset->getId());
        if (! $values) {
            return '';
        }

        // We might have many values selected in a list field (eg:
        // multi-selectbox, checkbox). As we want only one color,
        // arbitrary take the color of the first selected value.
        $value_id = $values[0]['id'];

        $decorators = $field->getDecorators();
        if (! isset($decorators[$value_id])) {
            return '';
        }

        return $decorators[$value_id]->tlp_color_name;
    }
}
