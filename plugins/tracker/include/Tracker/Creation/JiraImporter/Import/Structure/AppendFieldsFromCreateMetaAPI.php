<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\XML\IDGenerator;

final class AppendFieldsFromCreateMetaAPI implements AppendFieldsFromCreate
{
    public function __construct(
        private readonly JiraClient $wrapper,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return JiraFieldAPIRepresentation[]
     */
    public function appendFromCreate(array $fields_by_id, string $jira_project_key, string $jira_issue_type_id, IDGenerator $id_generator): array
    {
        $meta_url = ClientWrapper::JIRA_CORE_BASE_URL . "/issue/createmeta?projectKeys=" . urlencode($jira_project_key) .
            "&issuetypeIds=" . urlencode($jira_issue_type_id) . "&expand=projects.issuetypes.fields";

        $this->logger->debug('GET ' . $meta_url);
        $project_meta_content = $this->wrapper->getUrl($meta_url);

        if (! $project_meta_content || ! isset($project_meta_content['projects'][0]['issuetypes'][0]['fields'])) {
            return $fields_by_id;
        }

        $jira_fields = $project_meta_content['projects'][0]['issuetypes'][0]['fields'];
        foreach ($jira_fields as $jira_field_id => $jira_field) {
            $jira_field_api_representation = JiraFieldAPIRepresentation::buildFromAPIForSubmit(
                $jira_field_id,
                $jira_field,
                $id_generator
            );

            $fields_by_id[$jira_field_api_representation->getId()] = $jira_field_api_representation;
        }

        return $fields_by_id;
    }
}
