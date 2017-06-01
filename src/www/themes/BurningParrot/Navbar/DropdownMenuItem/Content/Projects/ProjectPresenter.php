<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Content\Projects;

use PFUser;
use Project;

class ProjectPresenter
{
    /** @var string */
    public $project_name;

    /** @var string */
    public $project_uri;

    /** @var string */
    public $project_config_uri;

    /** @var string */
    public $is_private;

    /** @var string */
    public $user_administers;

    /** @var string */
    public $user_belongs;

    public function __construct(
        $project_name,
        $project_uri,
        $project_config_uri,
        $is_private,
        $user_administers,
        $user_belongs
    ) {
        $this->project_name       = $project_name;
        $this->project_uri        = $project_uri;
        $this->project_config_uri = $project_config_uri;
        $this->is_private         = $is_private;
        $this->user_administers   = $user_administers;
        $this->user_belongs       = $user_belongs;
    }
}
