<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han <xiphux@gmail.com>
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

namespace Tuleap\Git\GitPHP;

/**
 * GitPHP GitObject
 *
 * Base class for all hash objects in a git repository
 *
 */

/**
 * Git Object class
 *
 * @abstract
 */
abstract class GitObject
{
    /**
     * project
     *
     * Stores the project internally
     *
     * @access protected
     */
    protected $project;

    /**
     * hash
     *
     * Stores the hash of the object internally
     *
     * @access protected
     */
    protected $hash;

    /**
     * projectReferenced
     *
     * Stores whether the project has been referenced into a pointer
     *
     * @access protected
     */
    protected $projectReferenced = false;

    /**
     * __construct
     *
     * Instantiates object
     *
     * @access public
     * @param mixed $project the project
     * @param string $hash object hash
     * @return mixed git object
     * @throws \Exception exception on invalid hash
     */
    public function __construct($project, $hash)
    {
        $this->project = $project;
        $this->SetHash($hash);
    }

    /**
     * GetProject
     *
     * Gets the project
     *
     * @access public
     * @return Project
     */
    public function GetProject() // @codingStandardsIgnoreLine
    {
        if ($this->projectReferenced) {
            $this->DereferenceProject();
        }

        return $this->project;
    }

    /**
     * GetHash
     *
     * Gets the hash
     *
     * @access public
     * @return string object hash
     */
    public function GetHash() // @codingStandardsIgnoreLine
    {
        return $this->hash;
    }

    /**
     * SetHash
     *
     * Attempts to set the hash of this object
     *
     * @param string $hash the hash to set
     * @throws \Exception on invalid hash
     * @access protected
     */
    protected function SetHash($hash) // @codingStandardsIgnoreLine
    {
        if (!(preg_match('/[0-9a-f]{40}/i', $hash))) {
            throw new \Exception(sprintf(dgettext("gitphp", 'Invalid hash %1$s'), $hash));
        }
        $this->hash = $hash;
    }

    /**
     * ReferenceProject
     *
     * Turns the project object into a reference pointer
     *
     * @access private
     */
    private function ReferenceProject() // @codingStandardsIgnoreLine
    {
        if ($this->projectReferenced) {
            return;
        }

        $this->project = $this->project->GetProject();

        $this->projectReferenced = true;
    }

    /**
     * DereferenceProject
     *
     * Turns the project reference pointer back into an object
     *
     * @access private
     */
    private function DereferenceProject() // @codingStandardsIgnoreLine
    {
        if (!$this->projectReferenced) {
            return;
        }

        $this->project = ProjectList::GetInstance()->GetProject($this->project);

        $this->projectReferenced = false;
    }
}
