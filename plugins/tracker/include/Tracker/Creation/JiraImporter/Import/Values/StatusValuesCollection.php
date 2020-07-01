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
     * @var ClientWrapper
     */
    private $wrapper;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ClientWrapper $wrapper, LoggerInterface $logger)
    {
        $this->wrapper = $wrapper;
        $this->logger = $logger;
    }

    public function initCollectionForProjectAndIssueType(string $jira_project_key, string $jira_issue_type_name): void
    {
        $this->logger->debug("Build status collection ...");
        $statuses_url     = "project/" . urlencode($jira_project_key) . "/statuses";

        $this->logger->debug("  GET " . $statuses_url);
        $statuses_content = $this->wrapper->getUrl($statuses_url);

        if ($statuses_content === null) {
            $this->logger->debug("No statuses defined");
            return;
        }

        foreach ($statuses_content as $statuses_content_per_issue_type) {
            if ($statuses_content_per_issue_type['name'] !== $jira_issue_type_name) {
                continue;
            }

            foreach ($statuses_content_per_issue_type['statuses'] as $status) {
                $this->addStatusInCollections($status);
            }
        }

        $this->logger->debug("Status collection successfully built.");
    }

    private function addStatusInCollections(array $status): void
    {
        $status_representation = JiraFieldAPIAllowedValueRepresentation::buildFromAPIResponseStatuses($status);
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
