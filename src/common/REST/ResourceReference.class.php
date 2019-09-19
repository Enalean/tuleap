<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

namespace Tuleap\REST;

class ResourceReference
{

    /** Use this variable as a placeholder for future route implementation */
    public const NO_ROUTE = 'route-not-yet-implemented';

    /**
     * @var int ID of the resource
     */
    public $id;

    /**
     * @var string URI of the resource
     */
    public $uri;

    public function build($id, $base_uri)
    {
        $this->id  = JsonCast::toInt($id);
        $this->uri = $base_uri . '/' . $id;
    }
}
