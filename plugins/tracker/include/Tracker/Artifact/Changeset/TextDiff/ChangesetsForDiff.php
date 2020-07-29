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

namespace Tuleap\Tracker\Artifact\Changeset\TextDiff;


class ChangesetsForDiff
{
    /**
     * @var ?\Tracker_Artifact_Changeset
     * @psalm-readonly
     */
    private $previous_changeset;
    /**
     * @var \Tracker_Artifact_Changeset
     * @psalm-readonly
     */
    private $next_changeset;
    /**
     * @var \Tracker_FormElement_Field_Text
     * @psalm-readonly
     */
    private $field_text;

    public function __construct(
        \Tracker_Artifact_Changeset $next_changeset,
        \Tracker_FormElement_Field_Text $field_text,
        ?\Tracker_Artifact_Changeset $previous_changeset
    ) {
        $this->previous_changeset = $previous_changeset;
        $this->next_changeset     = $next_changeset;
        $this->field_text         = $field_text;
    }

    public function getPreviousChangeset(): ?\Tracker_Artifact_Changeset
    {
        return $this->previous_changeset;
    }

    public function getNextChangeset(): \Tracker_Artifact_Changeset
    {
        return $this->next_changeset;
    }

    public function getFieldText(): \Tracker_FormElement_Field_Text
    {
        return $this->field_text;
    }
}
