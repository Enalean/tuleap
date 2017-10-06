<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
    private $tokens          = array();

    private $artifacts = array();

    public static function instance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new Cache();
        }
        return self::$instance;
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
}
