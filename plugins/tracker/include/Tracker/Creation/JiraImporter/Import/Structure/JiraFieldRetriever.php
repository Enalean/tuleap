<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;

class JiraFieldRetriever
{
    /**
     * @var ClientWrapper
     */
    private $wrapper;

    public function __construct(ClientWrapper $wrapper)
    {
        $this->wrapper = $wrapper;
    }

    /**
     * @return JiraFieldAPIRepresentation[]
     */
    public function getAllJiraFields(string $jira_project_key, string $jira_issue_type_name): array
    {
        $meta_url = "issue/createmeta?projectKeys=" . urlencode($jira_project_key) .
            "&issuetypeNames=" . urlencode($jira_issue_type_name) . "&expand=projects.issuetypes.fields";

        $project_meta_content = $this->wrapper->getUrl($meta_url);

        $fields_by_id = [];
        if (! $project_meta_content || ! isset($project_meta_content['projects'][0]['issuetypes'][0]['fields'])) {
            return $fields_by_id;
        }

        $jira_fields = $project_meta_content['projects'][0]['issuetypes'][0]['fields'];
        foreach ($jira_fields as $jira_field_id => $jira_field) {
            $jira_field_api_representation = JiraFieldAPIRepresentation::buildFromAPIResponseAndID(
                $jira_field_id,
                $jira_field
            );

            $fields_by_id[$jira_field_api_representation->getId()] = $jira_field_api_representation;
        }

        return $fields_by_id;
    }
}
