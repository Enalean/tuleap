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

namespace Tuleap\TestPlan\REST\v1;

use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;

class BacklogItemRepresentation
{
    /**
     * @var int
     * @psalm-readonly
     */
    public $id;
    /**
     * @var string
     * @psalm-readonly
     */
    public $label;
    /**
     * @var string
     * @psalm-readonly
     */
    public $short_type;
    /**
     * @var string
     * @psalm-readonly
     */
    public $color;
    /**
     * @var \Tuleap\Tracker\REST\Artifact\ArtifactReference
     * @psalm-readonly
     */
    public $artifact;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $can_add_a_test;

    public function __construct(\AgileDashboard_Milestone_Backlog_IBacklogItem $backlog_item, \PFUser $user)
    {
        $this->id         = JsonCast::toInt($backlog_item->id());
        $this->label      = $backlog_item->title();
        $this->short_type = $backlog_item->getShortType();
        $this->color      = $backlog_item->color();

        $artifact = $backlog_item->getArtifact();

        $this->artifact = ArtifactReference::build($artifact);

        $artifact_link_field  = $artifact->getAnArtifactLinkField($user);
        $this->can_add_a_test = $artifact_link_field && $artifact_link_field->userCanUpdate($user);
    }
}
