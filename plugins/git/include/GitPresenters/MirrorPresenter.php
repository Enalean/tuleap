<?php
/**
 * Copyright (c) Enalean, 2013 - 2015. All rights reserved
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

class GitPresenters_MirrorPresenter
{

    /**
     * @var int
     */
    public $mirror_id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $is_used;


    public function __construct(Git_Mirror_Mirror $mirror, $is_used)
    {
        $this->mirror_id = $mirror->id;
        $this->name      = $mirror->name;
        $this->is_used   = $is_used;
    }
}
