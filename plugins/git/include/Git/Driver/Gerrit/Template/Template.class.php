<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

/**
 * I'm a refs/meta/config template for Gerrit
 */
class Git_Driver_Gerrit_Template_Template
{

    /** @var int */
    private $id;

    /** @var int */
    private $group_id;

    /** @var String */
    private $name;

    /** @var String */
    private $content;

    public function __construct($id, $group_id, $name, $content = null)
    {
        $this->id       = $id;
        $this->group_id = $group_id;
        $this->name     = $name;
        $this->content  = $content;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return \Git_Driver_Gerrit_Template_Template
     */
    public function setContent($content)
    {
        $this->content = (string) $content;
        return $this;
    }

    public function getProjectId()
    {
        return $this->group_id;
    }

    /**
     * @param int $group_id
     *
     * @return True if this template belongs to the given project
     */
    public function belongsToProject($group_id)
    {
        return $this->group_id == $group_id;
    }
}
