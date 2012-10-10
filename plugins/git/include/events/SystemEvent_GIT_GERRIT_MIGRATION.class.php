<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once GIT_BASE_DIR .'/GitDao.class.php';
class SystemEvent_GIT_GERRIT_MIGRATION extends SystemEvent {

    const TYPE = "GIT_GERRIT_MIGRATION";

    /** @var GitDao */
    private $dao;
    
    public function process() {
        $repo_id = (int)$this->getParameter(0);
        $this->getDao()->switchToGerrit($repo_id);
 //       throw new ErrorException(' not implemented');
    }
    
    public function verbalizeParameters($with_link) {
        return  $this->parameters;
    }
    
    public function setGitDao(GitDao $dao) {
        $this->dao = $dao;
    }
    
    public function getDao() {
        if ($this->dao == null) {
            $this->dao = new GitDao();
        }
        return $this->dao;
    }
}

?>
