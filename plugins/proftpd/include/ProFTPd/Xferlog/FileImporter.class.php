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

namespace Tuleap\ProFTPd\Xferlog;

use \Exception;
use \PFUser;
use \ProjectManager;
use \UserManager;

class FileImporter {

    /** @var Dao */
    private $dao;

    /** @var Parser */
    private $parser;

    /** @var UserManager */
    private $user_manager;

    /** @var ProjectManager */
    private $project_manager;

    public function __construct(Dao $dao, Parser $parser, UserManager $user_manager, ProjectManager $project_manager) {
        $this->dao             = $dao;
        $this->parser          = $parser;
        $this->user_manager    = $user_manager;
        $this->project_manager = $project_manager;
    }

    public function import($filepath) {
        $fd = fopen($filepath, 'r');
        if ($fd) {
            while (($line = fgets($fd)) !== false) {
                $this->parseLine($line);
            }
        }
        fclose($fd);
    }

    private function parseLine($line) {
        try {
            $entry      = $this->parser->extract(trim($line));
            $user_id    = $this->getUserId($entry);
            $project_id = $this->getProjectId($entry);
            $this->dao->store($user_id, $project_id, $entry);
        } catch (Exception $exception) {
        }
    }

    private function getUserId(Entry $entry) {
        $user = $this->user_manager->getUserByUserName($entry->username);
        if ($user) {
            return $user->getId();
        }
        return 0;
    }

    private function getProjectId(Entry $entry) {
        $matches = array();
        if (preg_match('%^/([^/]+)/.*%', $entry->filename, $matches)) {
            $project = $this->project_manager->getProjectByUnixName($matches[1]);
            if ($project && !$project->isError()) {
                return $project->getId();
            }
        }
        return 0;
    }
}
