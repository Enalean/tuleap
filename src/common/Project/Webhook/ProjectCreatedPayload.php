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

use Tuleap\Webhook\Payload;

class ProjectCreatedPayload implements Payload
{
    /**
     * @var array
     */
    private $payload;

    public function __construct(\Project $project, $update_time)
    {
        $this->payload = $this->buildPayload($project, $update_time);
    }

    private function buildPayload(\Project $project, $update_time)
    {
        $creation_date = new \DateTime('@' . $project->getStartDate());
        $update_date   = new \DateTime("@$update_time");
        $owner         = $this->extractOwner($project);
        return array(
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
    }

    /**
     * @return \PFUser
     */
    private function extractOwner(\Project $project)
    {
        $admins = $project->getAdmins();
        return $admins[0];
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
