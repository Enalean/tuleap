<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring;

final class TargetFields
{
    /**
     * @var \Tracker_FormElement_Field_ArtifactLink
     * @psalm-readonly
     */
    private $artifact_link_field;
    /**
     * @var \Tracker_FormElement_Field_Text
     * @psalm-readonly
     */
    private $title_field;

    public function __construct(
        \Tracker_FormElement_Field_ArtifactLink $artifact_link_field,
        \Tracker_FormElement_Field_Text $title_field
    ) {
        $this->artifact_link_field = $artifact_link_field;
        $this->title_field         = $title_field;
    }

    /**
     * @psalm-mutation-free
     */
    public function getArtifactLinkField(): \Tracker_FormElement_Field_ArtifactLink
    {
        return $this->artifact_link_field;
    }

    /**
     * @psalm-mutation-free
     */
    public function getTitleField(): \Tracker_FormElement_Field_Text
    {
        return $this->title_field;
    }
}
