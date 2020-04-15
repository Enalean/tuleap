<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter;

class JiraTrackerBuilder
{
    /**
     * @throws JiraConnectionException
     */
    public function build(ClientWrapper $wrapper, string $project_key): array
    {
        $project_details = $wrapper->getUrl('/project/' . urlencode($project_key));

        $tracker_list = [];
        if (! $project_details || ! $project_details['issueTypes']) {
            return $tracker_list;
        }

        foreach ($project_details['issueTypes'] as $tracker) {
            if (! isset($tracker['id']) || ! isset($tracker['name'])) {
                throw new \LogicException('Tracker does not have an id or a name');
            }
            $tracker_list[] = [
                "id"   => $tracker['id'],
                "name" => $tracker['name']
            ];
        }

        return $tracker_list;
    }
}
