<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * A TreeNode that holds an Tracker_Artifact
 */
class ArtifactNode extends TreeNode
{

    public function __construct(Tracker_Artifact $artifact, ?array $data = null)
    {
        parent::__construct($data, $artifact->getId());
        $this->setObject($artifact);
    }

    /**
     * @return Tracker_Artifact
     */
    public function getArtifact()
    {
        return $this->getObject();
    }
}
