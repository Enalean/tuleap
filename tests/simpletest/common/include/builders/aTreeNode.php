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
 * @return \Test_TreeNode_Builder
 */
function aNode()
{
    return new Test_TreeNode_Builder();
}

class Test_TreeNode_Builder
{

    protected $children;
    private $data;
    private $object;
    private $id;

    public function __construct()
    {
        $this->children = array();
    }

    /**
     * @param vararg of Test_TreeNode_Builder
     * @return \Test_TreeNode_Builder
     */
    public function withChildren()
    {
        $args = func_get_args();
        foreach ($args as $node_builder) {
            $this->children[] = $node_builder->build();
        }
        return $this;
    }

    /**
     * @return \Test_TreeNode_Builder
     */
    public function withChild(Test_TreeNode_Builder $child_node_builder)
    {
        $this->children[] = $child_node_builder->build();
        return $this;
    }

    /**
     * @return \Test_TreeNode_Builder
     */
    public function withArtifact($artifact)
    {
        $this->data['artifact'] = $artifact;
        return $this;
    }

    /**
     * @return \Test_TreeNode_Builder
     */
    public function withObject($object)
    {
        $this->object = $object;
        return $this;
    }

    /** @return \Test_TreeNode_Builder */
    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return \TreeNode
     */
    public function build()
    {
        $node = new TreeNode($this->data, $this->id);
        $node->setChildren($this->children);
        $node->setObject($this->object);
        return $node;
    }
}
