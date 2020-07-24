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

namespace Tuleap\Label;

use PFUser;
use Project;
use Tuleap\Event\Dispatchable;

class LabeledItemCollection implements Dispatchable
{
    public const NAME = 'collect_labeled_items';

    /**
     * @var array LabeledItem[]
     */
    private $labeled_items = [];
    /**
     * @var Project
     */
    private $project;
    /**
     * @var int
     */
    private $total_size = 0;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var bool
     */
    private $are_there_items_user_cannot_see;
    /**
     * @var array
     */
    private $label_ids;
    /**
     * @var int
     */
    private $limit;
    /**
     * @var int
     */
    private $offset;

    public function __construct(
        Project $project,
        PFUser $user,
        array $label_ids,
        $limit,
        $offset
    ) {
        $this->project   = $project;
        $this->user      = $user;
        $this->label_ids = $label_ids;
        $this->limit     = $limit;
        $this->offset    = $offset;

        $this->are_there_items_user_cannot_see = false;
    }

    public function add(LabeledItem $labeled_item)
    {
        $this->labeled_items[] = $labeled_item;
    }

    /**
     * @return LabeledItem[]
     */
    public function getItems()
    {
        return $this->labeled_items;
    }

    /**
     * @return int
     */
    public function getTotalSize()
    {
        return $this->total_size;
    }

    public function thereAreItemsUserCannotSee()
    {
        $this->are_there_items_user_cannot_see = true;
    }

    /**
     * @return bool
     */
    public function areThereItemsUserCannotSee()
    {
        return $this->are_there_items_user_cannot_see;
    }

    /**
     * @return array
     */
    public function getLabelIds()
    {
        return $this->label_ids;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return PFUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $total_size
     */
    public function setTotalSize($total_size)
    {
        $this->total_size = $total_size;
    }
}
