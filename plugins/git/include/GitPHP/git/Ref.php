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
 * GitPHP Ref
 *
 * Base class for ref objects
 *
 */

use Tuleap\Git\Exceptions\GitRepoRefNotFoundException;

/**
 * Git Ref class
 *
 */
abstract class Ref extends GitObject
{

    /**
     * refName
     *
     * Stores the ref name
     *
     * @access protected
     */
    protected $refName;

    /**
     * refDir
     *
     * Stores the ref directory
     *
     * @access protected
     */
    protected $refDir;

    /**
     * __construct
     *
     * Instantiates ref
     *
     * @access public
     * @param mixed $project the project
     * @param string $refDir the ref directory
     * @param string $refName the ref name
     * @param string $refHash the ref hash
     * @throws \Exception if not a valid ref
     * @return mixed git ref
     */
    public function __construct($project, $refDir, $refName, $refHash = '')
    {
        $this->project = $project;
        $this->refDir = $refDir;
        $this->refName = $refName;
        if (!empty($refHash)) {
            $this->SetHash($refHash);
        }
    }

    /**
     * GetHash
     *
     * Gets the hash for this ref (overrides base)
     *
     * @access public
     * @return string object hash
     * @throws GitRepoRefNotFoundException
     */
    public function GetHash() // @codingStandardsIgnoreLine
    {
        if (empty($this->hash)) {
            $this->FindHash();
        }

        return parent::GetHash();
    }

    /**
     * FindHash
     *
     * Looks up the hash for the ref
     *
     * @access protected
     * @throws GitRepoRefNotFoundException if hash is not found
     */
    protected function FindHash() // @codingStandardsIgnoreLine
    {
        $exe = new GitExe($this->GetProject());
        $args = array();
        $args[] = '--hash';
        $args[] = '--verify';
        $args[] = escapeshellarg($this->GetRefPath());
        $hash = trim($exe->Execute(GitExe::SHOW_REF, $args));

        if (empty($hash)) {
            throw new GitRepoRefNotFoundException(sprintf('Invalid ref : %s', $this->GetRefPath()));
        }

        $this->SetHash($hash);
    }

    /**
     * GetName
     *
     * Gets the ref name
     *
     * @access public
     * @return string ref name
     */
    public function GetName() // @codingStandardsIgnoreLine
    {
        return $this->refName;
    }

    /**
     * GetDirectory
     *
     * Gets the ref directory
     *
     * @access public
     * @return string ref directory
     */
    public function GetDirectory() // @codingStandardsIgnoreLine
    {
        return $this->refDir;
    }

    /**
     * GetRefPath
     *
     * Gets the path to the ref within the project
     *
     * @access public
     * @return string ref path
     */
    public function GetRefPath() // @codingStandardsIgnoreLine
    {
        return 'refs/' . $this->refDir . '/' . $this->refName;
    }

    /**
     * GetFullPath
     *
     * Gets the path to the ref including the project path
     *
     * @access public
     * @return string full ref path
     */
    public function GetFullPath() // @codingStandardsIgnoreLine
    {
        return $this->GetProject()->GetPath() . '/' . $this->GetRefPath();
    }
}
