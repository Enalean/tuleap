<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class AgileDashboard_KanbanColumn
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $kanban_id;

    /**
     * @var string
     */
    private $label;

    /**
     * @var bool
     */
    private $is_open;

    /**
     * @var string
     */
    private $color;

    /**
     * @var int
     */
    private $limit;

     /**
      * @var bool
      */
    private $is_removable;

    public function __construct($id, $kanban_id, $label, $is_open, $color, $limit, $is_removable)
    {
        $this->id           = $id;
        $this->kanban_id    = $kanban_id;
        $this->label        = $label;
        $this->is_open      = $is_open;
        $this->color        = $color;
        $this->limit        = $limit;
        $this->is_removable = $is_removable;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getKanbanId()
    {
        return $this->kanban_id;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function isOpen()
    {
        return $this->is_open;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function isRemovable()
    {
        return $this->is_removable;
    }
}
