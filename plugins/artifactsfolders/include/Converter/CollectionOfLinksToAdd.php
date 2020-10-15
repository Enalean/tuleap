<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\ArtifactsFolders\Converter;

use Tuleap\Tracker\Artifact\Artifact;

class CollectionOfLinksToAdd
{
    /**
     * @var array
     */
    private $links = [];

    public function addALink(Artifact $folder, Artifact $item)
    {
        $folder_id = $folder->getId();
        $item_id   = $item->getId();

        if (! array_key_exists($folder_id, $this->links)) {
            $this->links[$folder_id] = ["folder" => $folder, "links" => []];
        }

        $this->links[$folder_id]["links"][$item_id] = $item;
    }

    public function getLinksToAddToFolder()
    {
        return $this->links;
    }
}
