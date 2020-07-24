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

class Git_Gitolite_Presenter_GitoliteConfPresenter
{

    /**
     * @var array
     */
    public $mirrors = [];

    /**
     * @var array
     */
    public $project_names;

    public function __construct(array $project_names, array $mirrors)
    {
        $this->project_names = $project_names;
        foreach ($mirrors as $mirror) {
            $this->mirrors[] = $mirror->owner->getUserName();
        }
    }

    public function has_mirror()
    {
        return count($this->mirrors);
    }
}
