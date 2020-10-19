<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\ChangesetValue\Text;

/**
 * @psalm-immutable
 */
class FollowUpPresenter
{
    /**
     * @var int
     */
    public $changeset_id;
    /**
     * @var int
     */
    public $field_id;
    /**
     * @var int
     */
    public $artifact_id;

    public function __construct(
        \Tuleap\Tracker\Artifact\Artifact $artifact,
        \Tracker_FormElement_Field_Text $field,
        \Tracker_Artifact_ChangesetValue_Text $changeset_value
    ) {
        $this->changeset_id = $changeset_value->getChangeset()->getId();
        $this->artifact_id  = $artifact->getId();
        $this->field_id     = $field->getId();
    }
}
