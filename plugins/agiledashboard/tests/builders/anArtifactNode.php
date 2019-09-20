<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../../../tests/simpletest/common/include/builders/aTreeNode.php';

/**
 * @return \Test_ArtifactNode_Builder
 */
function anArtifactNode()
{
    return new Test_ArtifactNode_Builder();
}

class Test_ArtifactNode_Builder extends Test_TreeNode_Builder
{

    private $data = null;
    private $artifact;

    public function withArtifact($artifact)
    {
        if (! $artifact instanceof Tracker_Artifact) {
            throw new InvalidArgumentException('Expected ' . Tracker_Artifact::class . 'got ' . get_class($artifact));
        }
        $this->artifact = $artifact;
        return $this;
    }

    /**
     * @return ArtifactNode
     */
    public function build()
    {
        $node = new ArtifactNode($this->artifact, $this->data);
        $node->setChildren($this->children);
        return $node;
    }
}
