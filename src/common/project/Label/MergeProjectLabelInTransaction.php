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

namespace Tuleap\Project\Label;

use Project;
use Tuleap\Event\Dispatchable;

class MergeProjectLabelInTransaction implements Dispatchable
{
    const NAME = 'mergeLabelInProject';
    /**
     * @var Project
     */
    private $project;
    /**
     * @var int
     */
    private $label_to_edit_id;
    /**
     * @var int[]
     */
    private $label_ids_to_merge;

    /**
     * @param Project $project
     * @param int $label_to_edit_id
     * @param int[] $label_ids_to_merge
     */
    public function __construct(Project $project, $label_to_edit_id, array $label_ids_to_merge)
    {
        $this->project            = $project;
        $this->label_to_edit_id   = $label_to_edit_id;
        $this->label_ids_to_merge = $label_ids_to_merge;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return int
     */
    public function getLabelToEditId()
    {
        return $this->label_to_edit_id;
    }

    /**
     * @return int[]
     */
    public function getLabelIdsToMerge()
    {
        return $this->label_ids_to_merge;
    }
}
