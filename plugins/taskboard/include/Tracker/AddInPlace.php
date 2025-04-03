<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Tracker;

use Tracker;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;

class AddInPlace
{
    /**
     * @var Tracker
     */
    private $child_tracker;
    /**
     * @var ArtifactLinkField
     */
    private $parent_artifact_link_field;

    public function __construct(Tracker $child_tracker, ArtifactLinkField $parent_artifact_link_field)
    {
        $this->child_tracker              = $child_tracker;
        $this->parent_artifact_link_field = $parent_artifact_link_field;
    }

    public function getChildTracker(): Tracker
    {
        return $this->child_tracker;
    }

    public function getParentArtifactLinkField(): ArtifactLinkField
    {
        return $this->parent_artifact_link_field;
    }
}
