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

namespace Tuleap\FRS\REST\v1;

use Tuleap\REST\JsonCast;
use FRSRelease;

class ReleaseRepresentation
{
    const ROUTE = 'frs_release';

    /**
     * @var id {@type int}
     */
    public $id;

    /**
     * @var $uri {@type string}
     */
    public $uri;

    /**
     * @var $name {@type string}
     */
    public $name;

    /**
     * @var $files {@type array}
     */
    public $files = array();

    /**
     * @var $changelog {@type string}
     */
    public $changelog;

    /**
     * @var $release_note {@type string}
     */
    public $release_note;

    /**
     * @var $resources {@type array}
     */
    public $resources;

    public function build(FRSRelease $release)
    {
        $this->id           = JsonCast::toInt($release->getReleaseID());
        $this->uri          = self::ROUTE ."/". urlencode($release->getReleaseID());
        $this->changelog    = $release->getChanges();
        $this->release_note = $release->getNotes();
        $this->name         = $release->getName();
        $this->package      = array(
            "id"   =>$release->getPackage()->getPackageID(),
            "name" => $release->getPackage()->getName()
        );
        $this->resources = array(
            "artifacts" => array(
                "uri" => $this->uri ."/artifacts"
            )
        );

        foreach ($release->getFiles() as $file) {
            $file_representation = new FileRepresentation();
            $file_representation->build($file);
            $this->files[] = $file_representation;
        }

    }
}
