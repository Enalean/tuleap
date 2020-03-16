<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\REST\JsonCast;

class FileInfoRepresentation
{
    /**
     * @var int ID of the file
     */
    public $id;

    /**
     * @var int ID of the user who created the file
     */
    public $submitted_by;

    /**
     * @var string Description of the file
     */
    public $description;

    /**
     * @var string Name of the file
     */
    public $name;

    /**
     * @var int Size of the file in bytes
     */
    public $size;

    /**
     * @var string Mime type
     */
    public $type;

    /**
     * @var string
     */
    public $html_url;

    /**
     * @var string
     */
    public $html_preview_url;

    public function build($id, $submitted_by, $description, $name, $filesize, $filetype, $html_url, $html_preview_url)
    {
        $this->id               = JsonCast::toInt($id);
        $this->submitted_by     = JsonCast::toInt($submitted_by);
        $this->description      = $description;
        $this->name             = $name;
        $this->size             = JsonCast::toInt($filesize);
        $this->type             = $filetype;
        $this->html_url         = $html_url;
        $this->html_preview_url = $html_preview_url;
        $this->uri              = 'artifact_files/' . $this->id;

        return $this;
    }
}
