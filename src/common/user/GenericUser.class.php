<?php

/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
class GenericUser extends PFUser{
    const NAME_PREFIX = 'forge__prjgen_';

    /**
     * @var Project
     */
    private $project;

    public function __construct(Project $project, $data = null) {
        parent::__construct($data);
        $this->project  = $project;
    }

    /**
     * @return string
     */
    public function getStatus() {
        return 'R';
    }

    /**
     * @return string
     */
    public function getRealName() {
        $project_name = $this->project->getUnixName();

        return self::NAME_PREFIX.$project_name;
    }

    /**
     * @return string
     */
    public function getUserName() {
        return $this->getRealName();
    }

    /**
     * @return string
     */
    public function getUnixUid() {
        return $this->getRealName();
    }

    public function getProject() {
        return $this->project;
    }
}
?>
