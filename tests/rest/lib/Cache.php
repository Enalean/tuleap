<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 *
 */

namespace Test\Rest;

class Cache
{
    private static $instance;

    private $project_ids     = array();
    private $tracker_ids     = array();
    private $user_groups_ids = array();
    private $user_ids        = array();
    private $tokens          = array();

    private $trackers  = [];
    private $artifacts = array();

    public static function instance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new Cache();
        }
        return self::$instance;
    }

    public function getTrackerInProject($project_name, $tracker_name)
    {
        $project_id = $this->getProjectId($project_name);
        if (isset($this->tracker_ids[$project_id][$tracker_name])) {
            return $this->tracker_ids[$project_id][$tracker_name];
        }
        throw new \Exception('Tracker name does not exist in cache');
    }

    public function getProjectId($project_name)
    {
        if (isset($this->project_ids[$project_name])) {
            return $this->project_ids[$project_name];
        }
        throw new \Exception('Project name not in cache');
    }

    public function setProjectIds($project_ids)
    {
        $this->project_ids = $project_ids;
    }

    public function getProjectIds()
    {
        return $this->project_ids;
    }

    public function setTrackerIds($tracker_ids)
    {
        $this->tracker_ids = $tracker_ids;
    }

    public function addTrackerRepresentations(array $tracker_representations)
    {
        $this->trackers = $this->trackers + $tracker_representations;
    }

    public function getTrackerRepresentations(): array
    {
        return $this->trackers;
    }

    public function getTrackerIds()
    {
        return $this->tracker_ids;
    }

    public function setUserGroupIds($user_groups_ids)
    {
        $this->user_groups_ids = $user_groups_ids;
    }

    public function getUserGroupIds()
    {
        return $this->user_groups_ids;
    }

    public function setArtifacts($tracker_id, $artifacts)
    {
        $this->artifacts[$tracker_id] = $artifacts;
    }

    public function getArtifacts($tracker_id)
    {
        if (isset($this->artifacts[$tracker_id])) {
            return $this->artifacts[$tracker_id];
        }
        return null;
    }

    public function setTokenForUser($username, $token)
    {
        $this->tokens[$username] = $token;
    }

    public function getTokenForUser($username)
    {
        if (isset($this->tokens[$username])) {
            return $this->tokens[$username];
        }
        return null;
    }

    public function setUserId($user)
    {
        $this->user_ids[$user["username"]] = $user["id"];
    }

    public function getUserIds()
    {
        return $this->user_ids;
    }
}
