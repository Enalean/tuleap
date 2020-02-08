<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tracker_Artifact;
use Tracker_HierarchyFactory;

class SourceOfAssociationDetector
{

    /**
     * @var Tracker_HierarchyFactory
     */
    private $hierarchy_factory;

    public function __construct(
        Tracker_HierarchyFactory $hierarchy_factory
    ) {
        $this->hierarchy_factory = $hierarchy_factory;
    }

    /**
     * Return true if $artifact_to_check is "parent of" $artifact_reference
     *
     * @todo: take planning into account
     *
     * When $artifact_to_check is a Release
     * And  $artifact_reference is a Sprint
     * And Release -> Sprint (in tracker hierarchy)
     * Then return True
     *
     *
     * @return bool
     */
    public function isChild(Tracker_Artifact $artifact_to_check, Tracker_Artifact $artifact_reference)
    {
        $children = $this->hierarchy_factory->getChildren($artifact_to_check->getTrackerId());

        return in_array($artifact_reference->getTracker(), $children);
    }
}
