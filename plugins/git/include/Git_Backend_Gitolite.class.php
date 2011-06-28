<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'Git_Backend_Interface.php';

class Git_Backend_Gitolite implements Git_Backend_Interface {
    /**
     * @var Git_GitoliteDriver
     */
    protected $driver;

    /**
     * @var GitDao
     */
    protected $dao;

    public function __construct($driver) {
        $this->driver = $driver;
    }

    /**
     * Create new reference
     *
     * @see plugins/git/include/Git_Backend_Interface::createReference()
     * @param GitRepository $repository
     */
    public function createReference($repository) {
        $this->driver->init($repository->getProject(), $repository->getName());
        $this->driver->push();
        $id = $this->getDao()->save($repository);
    }

    public function isInitialized($repository) {
        $masterExists = $this->driver->masterExists($this->getGitRootPath().'/'.$repository->getPath());
        if ($masterExists) {
            $this->getDao()->initialize($repository->getId());
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return URL to access the respository for remote git commands
     *
     * @param GitRepository $repository
     *
     * @return String
     */
    public function getAccessUrl($repository) {
        $serverName  = $_SERVER['SERVER_NAME'];
        return  'gitolite@'.$serverName.':'.$repository->getProject()->getUnixName().'/'.$repository->getName().'.git';
    }
    
    public function getGitRootPath() {
        return '/usr/com/gitolite/repositories/';
    }

    /**
     * Wrapper for GitDao
     * 
     * @return GitDao
     */
    protected function getDao() {
        if (!$this->dao) {
            $this->dao = new GitDao();
        }
        return $this->dao;
    }
}

?>