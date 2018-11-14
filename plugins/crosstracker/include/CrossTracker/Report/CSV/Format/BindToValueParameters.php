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

namespace Tuleap\CrossTracker\Report\CSV\Format;

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;

class BindToValueParameters extends BindParameters
{
    /**
     * @var \Tracker_Artifact_ChangesetValue
     */
    private $changeset_value;

    public function __construct(
        \Tracker_FormElement_Field_List $field,
        \Tracker_Artifact_ChangesetValue $changeset_value
    ) {
        parent::__construct($field);
        $this->changeset_value = $changeset_value;
    }

    /**
     * @return \Tracker_Artifact_ChangesetValue
     */
    public function getChangesetValue()
    {
        return $this->changeset_value;
    }
}
