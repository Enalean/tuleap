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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class TroveCat implements JsonSerializable
{

    public const ROOT_ID = 0;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $shortname;

    /**
     * @var string
     */
    private $fullname;

    /**
     * @var TroveCat[]
     */
    private $children = [];

    public function __construct($id, $shortname, $fullname)
    {
        $this->id        = $id;
        $this->shortname = $shortname;
        $this->fullname  = $fullname;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getShortname()
    {
        return $this->shortname;
    }

    public function getFullname()
    {
        return $this->fullname;
    }

    public function addChildren(TroveCat $trove_cat): self
    {
        $this->children[] = $trove_cat;
        return $this;
    }

    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'shortname' => $this->shortname,
            'fullname' => $this->fullname,
            'children' => $this->children
        ];
    }
}
