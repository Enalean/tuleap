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

class Codendi_Request_TestBuilder
{

    /**
     * @var array
     */
    private $params = array();

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function with($param_name, $param_value)
    {
        $this->params[$param_name] = $param_value;
        return $this;
    }

    public function withParams(array $params)
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    public function withUri($uri)
    {
        $this->withParams($this->extractParamsFromUri($uri));
        return $this;
    }

    public function withUser(PFUser $user)
    {
        $this->user = $user;
        return $this;
    }

    public function withProjectManager(ProjectManager $project_manager)
    {
        $this->project_manager = $project_manager;
        return $this;
    }

    private function buildUser()
    {
        $user = $this->user ? $this->user : new PFUser(['language_id' => 'en']);
        return $user;
    }

    public function build()
    {
        $request = new Codendi_Request($this->params, $this->project_manager);
        $request->setCurrentUser($this->buildUser());
        return $request;
    }

    private function extractParamsFromUri($uri)
    {
        $query  = $this->extractQueryFromUri($uri);
        $params = $this->extractParamsFromQuery($query);

        return $params;
    }

    private function extractQueryFromUri($uri)
    {
        $uri_parts = parse_url($uri);
        return isset($uri_parts['query']) ? $uri_parts['query'] : '';
    }

    private function extractParamsFromQuery($query)
    {
        $params = array();
        if ($query === '') {
            return $params;
        }

        foreach (explode('&', $query) as $param_name_and_value) {
            list($param_name, $param_value) = explode('=', $param_name_and_value);
            $params[$param_name] = $param_value;
        }

        return $params;
    }
}

function aRequest()
{
    return new Codendi_Request_TestBuilder();
}
