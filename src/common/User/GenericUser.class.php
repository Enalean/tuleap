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
class GenericUser extends PFUser
{
    public const NAME_PREFIX = 'forge__prjgen_';
    public const REAL_NAME = 'Generic User For Project';

    /**
     * @var Project
     */
    private $project;

    public function __construct(Project $project, PFUser $pfuser, $suffix)
    {
        parent::__construct($pfuser->toRow());
        $this->setStatus(PFUser::STATUS_RESTRICTED);
        $this->setRealName(self::REAL_NAME);
        $this->setUserName(self::NAME_PREFIX . $project->getUnixName() . $suffix);
        $this->project = $project;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }
}
