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
 */

namespace Tuleap\Project\Webhook;

use Http_Client;
use Project;

class Webhook
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $url;
    /**
     * @var Http_Client
     */
    private $http_client;

    public function __construct($id, $url, Http_Client $http_client)
    {
        $this->http_client = $http_client;
        $this->id          = $id;
        $this->url         = $url;
    }

    public function send(Project $project, $update_time)
    {
        $this->buildRequest($project, $update_time);

        try {
            $this->http_client->doRequest();
        } catch (\Http_ClientException $ex) {
        }

        $this->http_client->close();
    }

    private function buildRequest(Project $project, $update_time)
    {
        $options = array(
            CURLOPT_URL         => $this->url,
            CURLOPT_POST        => true,
            CURLOPT_HEADER      => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_POSTFIELDS  => $this->getRequestBody($project, $update_time)
        );
        $this->http_client->addOptions($options);
    }

    private function getRequestBody(Project $project, $update_time)
    {
        $creation_date = new \DateTime('@' . $project->getStartDate());
        $update_date   = new \DateTime("@$update_time");
        $owner         = $this->extractOwner($project);
        $payload = array(
            'created_at'          => $creation_date->format('c'),
            'updated_at'          => $update_date->format('c'),
            'event_name'          => 'project_create',
            'name'                => $project->getUnconvertedPublicName(),
            'owner_id'            => (int) $owner->getId(),
            'owner_email'         => $owner->getEmail(),
            'owner_name'          => $owner->getRealName(),
            'path'                => $project->getUnixName(),
            'path_with_namespace' => $project->getUnixName(),
            'project_id'          => (int) $project->getID(),
            'project_visibility'  => $project->getAccess()
        );

        return http_build_query(array('payload' => json_encode($payload)));
    }

    /**
     * @return \PFUser
     */
    private function extractOwner(Project $project)
    {
        $admins = $project->getAdmins();
        return $admins[0];
    }
}
