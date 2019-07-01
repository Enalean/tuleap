<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman;

use Docman_CloneItemsVisitor;
use Docman_Folder;
use Docman_ItemFactory;
use LogicException;
use Project;

final class DestinationCloneItem
{
    private const CLONE_ROOT_PARENT_ID = 0;

    /**
     * @var int
     */
    private $parent_folder_id;
    /**
     * @var int
     */
    private $destination_project_id;

    private function __construct(int $parent_folder_id, int $destination_project_id)
    {
        $this->parent_folder_id       = $parent_folder_id;
        $this->destination_project_id = $destination_project_id;
    }

    public static function fromNewParentFolder(Docman_Folder $folder) : self
    {
        return new self((int) $folder->getId(), (int) $folder->getGroupId());
    }

    public static function fromDestinationProject(Docman_ItemFactory $item_factory, Project $destination_project) : self
    {
        $project_id = $destination_project->getID();
        if ($item_factory->getRoot($project_id) !== null) {
            throw new LogicException(
                sprintf(
                    'The destination project #%d can only have one root item, you are trying to create a second one',
                    $destination_project->getID()
                )
            );
        }
        return new self(self::CLONE_ROOT_PARENT_ID, (int) $project_id);
    }

    public function getNewParentID() : int
    {
        return $this->parent_folder_id;
    }

    public function getCloneItemsVisitor() : Docman_CloneItemsVisitor
    {
        return new Docman_CloneItemsVisitor($this->destination_project_id);
    }
}
