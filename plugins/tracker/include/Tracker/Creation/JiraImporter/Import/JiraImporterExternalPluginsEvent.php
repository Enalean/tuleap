<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import;

use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentationCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;

class JiraImporterExternalPluginsEvent implements Dispatchable
{
    public const NAME = 'jiraImporterExternalPluginsEvent';

    /**
     * @var SimpleXMLElement
     */
    private $xml_tracker;

    /**
     * @var PlatformConfiguration
     */
    private $jira_platform_configuration;

    /**
     * @var IssueAPIRepresentationCollection
     * @psalm-readonly
     */
    private $issue_representation_collection;

    /**
     * @var JiraUserRetriever
     */
    private $jira_user_retriever;

    /**
     * @var JiraClient
     * @readonly
     */
    private $jira_client;

    /**
     * @var LoggerInterface
     * @readonly
     */
    private $logger;

    /**
     * @var FieldMappingCollection
     * @readonly
     */
    private $field_mapping_collection;

    public function __construct(
        SimpleXMLElement $xml_tracker,
        PlatformConfiguration $jira_platform_configuration,
        IssueAPIRepresentationCollection $issue_representation_collection,
        JiraUserRetriever $jira_user_retriever,
        JiraClient $jira_client,
        LoggerInterface $logger,
        FieldMappingCollection $field_mapping_collection,
    ) {
        $this->xml_tracker                     = $xml_tracker;
        $this->jira_platform_configuration     = $jira_platform_configuration;
        $this->issue_representation_collection = $issue_representation_collection;
        $this->jira_user_retriever             = $jira_user_retriever;
        $this->jira_client                     = $jira_client;
        $this->logger                          = $logger;
        $this->field_mapping_collection        = $field_mapping_collection;
    }

    public function getXmlTracker(): SimpleXMLElement
    {
        return $this->xml_tracker;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getJiraPlatformConfiguration(): PlatformConfiguration
    {
        return $this->jira_platform_configuration;
    }

    public function getIssueRepresentationCollection(): IssueAPIRepresentationCollection
    {
        return $this->issue_representation_collection;
    }

    public function getJiraClient(): JiraClient
    {
        return $this->jira_client;
    }

    public function getJiraUserRetriever(): JiraUserRetriever
    {
        return $this->jira_user_retriever;
    }

    public function getFieldMappingCollection(): FieldMappingCollection
    {
        return $this->field_mapping_collection;
    }
}
