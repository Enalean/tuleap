<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

use ProjectManager;
use UserDao;
use UserManager;

class FileImporter
{

    /** @var Dao */
    private $dao;

    /** @var Parser */
    private $parser;

    /** @var UserManager */
    private $user_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var int */
    private $nb_lines_imported;

    /** @var string[] */
    private $errors;

    /** @var string */
    private $base_dir;

    /**
     * @var UserDao
     */
    private $user_dao;

    /**
     * @var array
     */
    private $user_last_access_cache = [];

    public function __construct(
        Dao $dao,
        Parser $parser,
        UserManager $user_manager,
        ProjectManager $project_manager,
        UserDao $user_dao,
        $base_dir
    ) {
        $this->dao             = $dao;
        $this->parser          = $parser;
        $this->user_manager    = $user_manager;
        $this->project_manager = $project_manager;
        $this->base_dir        = $base_dir;
        $this->user_dao        = $user_dao;
    }

    public function getNbImportedLines()
    {
        return $this->nb_lines_imported;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function import($filepath)
    {
        $latest_timestamp = $this->dao->searchLatestEntryTimestamp();
        $this->parseFile($filepath, $latest_timestamp);
        $this->updateLastAccessDates();
    }

    private function parseFile($filepath, $latest_timestamp)
    {
        $this->nb_lines_imported = 0;
        $fd                      = fopen($filepath, 'r');
        if ($fd) {
            while (($line = fgets($fd)) !== false) {
                if ($this->parseLine($line, $latest_timestamp)) {
                    $this->nb_lines_imported++;
                }
            }
        }
        fclose($fd);
    }

    private function parseLine($line, $latest_timestamp)
    {
        try {
            $entry = $this->parser->extract(trim($line));
            if ($entry->current_time >= $latest_timestamp) {
                $user_id    = $this->getUserId($entry, $line);
                $project_id = $this->getProjectId($entry, $line);
                $this->cacheUserLastAccessDate($user_id, $entry->current_time);
                return $this->dao->store($user_id, $project_id, $entry);
            }
        } catch (InvalidEntryException $exception) {
            $this->errors[] = $exception->getMessage();
        }
        return false;
    }

    private function getUserId(Entry $entry, $line)
    {
        $user = $this->user_manager->getUserByUserName($entry->username);
        if ($user) {
            return $user->getId();
        }
        $this->errors[] = 'Unable to identify user in log line: ' . $line;
        return 0;
    }

    private function getProjectId(Entry $entry, $line)
    {
        $project_name = $this->getProjectUnixName($entry);
        if ($project_name) {
            $project = $this->project_manager->getProjectByUnixName($project_name);
            if ($project && ! $project->isError()) {
                return $project->getId();
            }
        }
        $this->errors[] = 'Unable to identify project in log line: ' . $line;
        return 0;
    }

    private function getProjectUnixName(Entry $entry)
    {
        if (strpos($entry->filename, $this->base_dir) === 0) {
            $entry->filename = substr($entry->filename, strlen($this->base_dir));
        }
        $matches = [];
        if (preg_match('%^/([^/]+)/.*%', $entry->filename, $matches)) {
            return $matches[1];
        }
    }

    private function cacheUserLastAccessDate($user_id, $timestamp)
    {
        if (isset($this->user_last_access_cache[$user_id]) && $this->user_last_access_cache[$user_id] >= $timestamp) {
            return;
        }

        $this->user_last_access_cache[$user_id] = $timestamp;
    }

    private function updateLastAccessDates()
    {
        foreach ($this->user_last_access_cache as $user_id => $timestamp) {
            $this->user_dao->storeLastAccessDate($user_id, $timestamp);
        }
    }
}
