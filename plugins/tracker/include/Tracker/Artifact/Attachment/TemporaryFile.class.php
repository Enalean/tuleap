<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class Tracker_Artifact_Attachment_TemporaryFile
{

    private $id;
    private $name;
    private $tempname;
    private $description;
    private $current_offset;
    private $creator_id;
    private $size;
    private $mimetype;

    public function __construct($id, $name, $tempname, $description, $current_offset, $creator_id, $size, $mimetype)
    {
        $this->id             = $id;
        $this->name           = $name;
        $this->tempname       = $tempname;
        $this->description    = $description;
        $this->current_offset = $current_offset;
        $this->creator_id     = $creator_id;
        $this->size           = $size;
        $this->mimetype       = $mimetype;
    }

    public function getTemporaryName()
    {
        return $this->tempname;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCurrentChunkOffset()
    {
        return $this->current_offset;
    }

    public function getCreatorId()
    {
        return $this->creator_id;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->mimetype;
    }

    public function setSize($size)
    {
        $this->size = $size;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
