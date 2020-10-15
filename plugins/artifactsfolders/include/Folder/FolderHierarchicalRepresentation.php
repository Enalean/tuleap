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

namespace Tuleap\ArtifactsFolders\Folder;

use Tuleap\Tracker\Artifact\Artifact;

class FolderHierarchicalRepresentation
{
    /**
     * @var Artifact
     */
    private $folder;

    /**
     * @var FolderHierarchicalRepresentationCollection
     */
    private $children;

    /**
     * @var int
     */
    private $parent_id;

    public function __construct(Artifact $folder, $parent_id)
    {
        $this->folder    = $folder;
        $this->parent_id = $parent_id;

        $this->children = new FolderHierarchicalRepresentationCollection();
    }

    public function addChild(FolderHierarchicalRepresentation $folder)
    {
        $this->children->add($folder);
    }

    public function getFolder()
    {
        return $this->folder;
    }

    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        return $this->parent_id;
    }
}
