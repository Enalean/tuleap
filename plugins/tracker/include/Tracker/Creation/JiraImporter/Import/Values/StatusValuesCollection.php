<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Values;

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldAPIAllowedValueRepresentation;

class StatusValuesCollection
{
    private const DONE_STATUS_CATEGORY = 'done';

    /**
     * @var JiraFieldAPIAllowedValueRepresentation[]
     */
    private $all_values = [];

    /**
     * @var JiraFieldAPIAllowedValueRepresentation[]
     */
    private $open_values = [];

    /**
     * @var JiraFieldAPIAllowedValueRepresentation[]
     */
    private $closed_values = [];

    /**
     * @var JiraClient
     */
    private $jira_client;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(JiraClient $jira_client, LoggerInterface $logger)
    {
        $this->jira_client = $jira_client;
        $this->logger      = $logger;
    }

    public function initCollectionForProject(string $jira_project_key, IDGenerator $id_generator): void
    {
        $this->logger->debug("Build status collection for project ...");

        $statuses_content = $this->retrieverStatusesContent($jira_project_key);
        if ($statuses_content === null) {
            return;
        }

        foreach ($statuses_content as $statuses_content_per_issue_type) {
            foreach ($statuses_content_per_issue_type['statuses'] as $status) {
                $this->addStatusInCollections($status, $id_generator);
            }
        }

        $this->logger->debug("Status collection successfully built.");
    }

    public function initCollectionForProjectAndIssueType(string $jira_project_key, string $jira_issue_type_id, IDGenerator $id_generator): void
    {
        $this->logger->debug("Build status collection ...");

        $statuses_content = $this->retrieverStatusesContent($jira_project_key);
        if ($statuses_content === null) {
            return;
        }

        foreach ($statuses_content as $statuses_content_per_issue_type) {
            if ($statuses_content_per_issue_type['id'] !== $jira_issue_type_id) {
                continue;
            }

            foreach ($statuses_content_per_issue_type['statuses'] as $status) {
                $this->addStatusInCollections($status, $id_generator);
            }
        }

        $this->logger->debug("Status collection successfully built.");
    }

    private function retrieverStatusesContent(string $jira_project_key): ?array
    {
        $statuses_url = ClientWrapper::JIRA_CORE_BASE_URL . "/project/" . urlencode($jira_project_key) . "/statuses";

        $this->logger->debug("  GET " . $statuses_url);
        $statuses_content = $this->jira_client->getUrl($statuses_url);

        if ($statuses_content === null) {
            $this->logger->debug("No statuses defined");
            return null;
        }

        return $statuses_content;
    }

    public function initCollectionWithValues(array $open_values, array $closed_values): void
    {
        $this->closed_values = $closed_values;
        $this->open_values   = $open_values;
        $this->all_values    = array_merge($open_values, $closed_values);
    }

    private function addStatusInCollections(array $status, IDGenerator $id_generator): void
    {
        $status_representation = JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses($status, $id_generator);

        $this->all_values[] = $status_representation;

        if (! isset($status['statusCategory']['key'])) {
            return;
        }

        if ($status['statusCategory']['key'] !== self::DONE_STATUS_CATEGORY) {
            $this->open_values[] = $status_representation;
        } else {
            $this->closed_values[] = $status_representation;
        }
    }

    /**
     * @return JiraFieldAPIAllowedValueRepresentation[]
     */
    public function getAllValues(): array
    {
        return $this->all_values;
    }

    /**
     * @return JiraFieldAPIAllowedValueRepresentation[]
     */
    public function getOpenValues(): array
    {
        return $this->open_values;
    }

    /**
     * @return JiraFieldAPIAllowedValueRepresentation[]
     */
    public function getClosedValues(): array
    {
        return $this->closed_values;
    }
}
