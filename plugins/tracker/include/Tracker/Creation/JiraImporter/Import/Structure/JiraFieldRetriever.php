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

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\XML\IDGenerator;

class JiraFieldRetriever
{
    private const PRIORITY_URL = ClientWrapper::JIRA_CORE_BASE_URL . '/priority';

    public function __construct(
        private readonly JiraClient $wrapper,
        private readonly LoggerInterface $logger,
        private readonly AppendFieldsFromCreate $append_field_from_create,
    ) {
    }

    /**
     * @return JiraFieldAPIRepresentation[]
     */
    public function getAllJiraFields(string $jira_project_key, string $jira_issue_type_id, IDGenerator $id_generator): array
    {
        $fields_by_id = [];

        $fields_by_id = $this->appendFromCreateMeta($fields_by_id, $jira_project_key, $jira_issue_type_id, $id_generator);
        $fields_by_id = $this->appendFromEditMeta($fields_by_id, $jira_project_key, $jira_issue_type_id, $id_generator);

        return $fields_by_id;
    }

    /**
     * @return JiraFieldAPIRepresentation[]
     */
    private function appendFromCreateMeta(array $fields_by_id, string $jira_project_key, string $jira_issue_type_id, IDGenerator $id_generator): array
    {
        return $this->append_field_from_create->appendFromCreate(
            $fields_by_id,
            $jira_project_key,
            $jira_issue_type_id,
            $id_generator,
        );
    }

    /**
     * @return JiraFieldAPIRepresentation[]
     */
    private function appendFromEditMeta(array $fields_by_id, string $jira_project_key, string $jira_issue_type_id, IDGenerator $id_generator): array
    {
        $params = [
            'jql'        => 'project="' . $jira_project_key . '" AND issuetype=' . $jira_issue_type_id,
            'expand'     => 'editmeta',
            'startAt'    => 0,
            'maxResults' => 1,
        ];

        $get_one_issue_url =  ClientWrapper::JIRA_CORE_BASE_URL . '/search?' . http_build_query($params);
        try {
            $this->logger->debug('GET ' . $get_one_issue_url);

            $one_issue = $this->wrapper->getUrl($get_one_issue_url);
            if (! isset($one_issue['issues']) || count($one_issue['issues']) !== 1 || ! isset($one_issue['issues'][0]['editmeta'])) {
                return $fields_by_id;
            }

            foreach ($one_issue['issues'][0]['editmeta']['fields'] as $jira_field_id => $jira_field) {
                if (isset($fields_by_id[$jira_field_id])) {
                    continue;
                }

                $jira_field_api_representation = JiraFieldAPIRepresentation::buildFromAPIForUpdate(
                    $jira_field_id,
                    $jira_field,
                    $id_generator
                );

                $fields_by_id[$jira_field_api_representation->getId()] = $jira_field_api_representation;
            }

            $fields_by_id = $this->addPriority($fields_by_id, $one_issue);

            return $fields_by_id;
        } catch (JiraConnectionException $exception) {
            $this->logger->warning(sprintf('GET %s error (%d): %s', $get_one_issue_url, $exception->getCode(), $exception->getMessage()));
        }
        return $fields_by_id;
    }

    /**
     * @return JiraFieldAPIRepresentation[]
     */
    private function addPriority(array $fields_by_id, array $one_issue): array
    {
        if (isset($fields_by_id[AlwaysThereFieldsExporter::JIRA_PRIORITY_NAME]) || ! isset($one_issue['issues'][0]['fields']['priority'])) {
            return $fields_by_id;
        }

        try {
            $this->logger->debug('GET ' . self::PRIORITY_URL);
            $payload = $this->wrapper->getUrl(self::PRIORITY_URL);
            if (! $payload) {
                return $fields_by_id;
            }
            $priority_values = [];
            foreach ($payload as $value) {
                if (! isset($value['id'], $value['name'])) {
                    continue;
                }
                $priority_values[] = JiraFieldAPIAllowedValueRepresentation::buildFromIDAndName(
                    (int) $value['id'],
                    $value['name'],
                );
            }

            $fields_by_id[AlwaysThereFieldsExporter::JIRA_PRIORITY_NAME] = new JiraFieldAPIRepresentation(
                AlwaysThereFieldsExporter::JIRA_PRIORITY_NAME,
                'Priority',
                false,
                AlwaysThereFieldsExporter::JIRA_PRIORITY_NAME,
                $priority_values,
                true,
            );

            return $fields_by_id;
        } catch (JiraConnectionException | \JsonException $exception) {
            $this->logger->warning(sprintf('%s raised a failure (%s). No priorities exported', self::PRIORITY_URL, $exception->getMessage()));
        }

        return $fields_by_id;
    }
}
