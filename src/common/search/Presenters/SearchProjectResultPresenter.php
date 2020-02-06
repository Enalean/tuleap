<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Search_SearchProjectResultPresenter
{

    /** @var  string */
    private $project_name;

    /** @var  string */
    private $project_unix_name;

    /** @var  string */
    private $project_description;

    public function __construct(array $result)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $this->project_name        = $result['group_name'];
        $this->project_unix_name   = $result['unix_group_name'];
        $this->project_description = $hp->purify($result['short_description'], CODENDI_PURIFIER_BASIC, $result['group_id']);
    }

    public function project_name()
    {
        return $this->project_name;
    }

    public function project_uri()
    {
        return '/projects/' . $this->project_unix_name;
    }

    public function project_unix_name()
    {
        return $this->project_unix_name;
    }

    public function project_description()
    {
        return $this->project_description;
    }
}
