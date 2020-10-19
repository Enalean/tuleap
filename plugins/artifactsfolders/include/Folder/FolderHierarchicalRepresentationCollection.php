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

use Codendi_HTMLPurifier;
use Tuleap\Tracker\Artifact\Artifact;

class FolderHierarchicalRepresentationCollection
{
    public const DEFAULT_PREFIX = ' ';

    /** @var FolderHierarchicalRepresentation[] */
    private $collection = [];

    public function add(FolderHierarchicalRepresentation $folder_hierarchical_representation)
    {
        $folder_id = $folder_hierarchical_representation->getFolder()->getId();

        $this->collection[$folder_id] = $folder_hierarchical_representation;
    }

    public function contains(Artifact $artifact)
    {
        return isset($this->collection[$artifact->getId()]);
    }

    /** @return FolderHierarchicalRepresentation */
    public function get(Artifact $artifact)
    {
        return $this->collection[$artifact->getId()];
    }

    /** @return FolderHierarchicalRepresentation | null */
    public function getById($artifact_id)
    {
        if (isset($this->collection[$artifact_id])) {
            return $this->collection[$artifact_id];
        }

        return null;
    }

    public function toArray()
    {
        return $this->collection;
    }

    public function collectOptions(array &$options, ?Artifact $current_folder = null, $prefix = self::DEFAULT_PREFIX)
    {
        if ($prefix === self::DEFAULT_PREFIX) {
            $prefix_for_children = "└─$prefix";
        } else {
            $prefix_for_children = "&nbsp;&nbsp;&nbsp;$prefix";
        }

        $purify = Codendi_HTMLPurifier::instance();
        foreach ($this->collection as $folder_representation) {
            $folder = $folder_representation->getFolder();
            $selected = '';
            if ($current_folder && $current_folder->getId() === $folder->getId()) {
                $selected = 'selected';
            }
            $option = '<option value="' . $folder->getId() . '" ' . $selected . '>';
            $option .= $prefix;
            $option .= $purify->purify($folder->getTitle());
            $option .= '</option>';
            $options[] = $option;

            $folder_representation->getChildren()->collectOptions($options, $current_folder, $prefix_for_children);
        }
    }
}
