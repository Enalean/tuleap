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

namespace Tuleap\Timetracking\JiraImporter;

use PFUser;
use ProjectUGroup;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Timetracking\JiraImporter\Configuration\JiraTimetrackingConfigurationRetriever;
use Tuleap\Timetracking\JiraImporter\Worklog\WorklogRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentationCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use XML_SimpleXMLCDATAFactory;

class JiraXMLExport
{
    /**
     * @var WorklogRetriever
     */
    private $worklog_retriever;

    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $cdata_factory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JiraUserRetriever
     */
    private $jira_user_retriever;

    public function __construct(
        WorklogRetriever $worklog_retriever,
        XML_SimpleXMLCDATAFactory $cdata_factory,
        JiraUserRetriever $jira_user_retriever,
        LoggerInterface $logger,
    ) {
        $this->worklog_retriever   = $worklog_retriever;
        $this->cdata_factory       = $cdata_factory;
        $this->jira_user_retriever = $jira_user_retriever;
        $this->logger              = $logger;
    }

    public function exportJiraTimetracking(
        SimpleXMLElement $xml_tracker,
        PlatformConfiguration $platform_configuration,
        IssueAPIRepresentationCollection $issue_representation_collection,
    ): void {
        $this->logger->debug("Export timetracking");

        $xml_timetracking = $this->exportDefaultTimetrackingConfiguration($xml_tracker);

        $this->exportTimes(
            $xml_timetracking,
            $platform_configuration,
            $issue_representation_collection
        );
    }

    private function exportDefaultTimetrackingConfiguration(SimpleXMLElement $xml_tracker): SimpleXMLElement
    {
        $this->logger->debug("Export default timetracking configuration");

        $xml_timetracking = $xml_tracker->addChild('timetracking');
        $xml_timetracking->addAttribute('is_enabled', "1");

        $xml_timetracking_permissions      = $xml_timetracking->addChild('permissions');
        $xml_timetracking_permission_write = $xml_timetracking_permissions->addChild('write');

        $project_member_ugroup_name = ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS];
        $this->cdata_factory->insert(
            $xml_timetracking_permission_write,
            "ugroup",
            $project_member_ugroup_name
        );

        return $xml_timetracking;
    }

    private function exportTimes(
        SimpleXMLElement $xml_timetracking,
        PlatformConfiguration $platform_configuration,
        IssueAPIRepresentationCollection $issue_representation_collection,
    ): void {
        if (! $platform_configuration->isConfigurationAllowed(JiraTimetrackingConfigurationRetriever::CONFIGURATION_KEY)) {
            $this->logger->debug("Jira platform does not have timetracking configured to be imported. Skipping.");
            return;
        }

        $this->logger->debug("Export saved times");
        foreach ($issue_representation_collection->getIssueRepresentationCollection() as $issue_representation) {
            $worklogs = $this->worklog_retriever->getIssueWorklogsFromAPI($issue_representation);
            foreach ($worklogs as $worklog) {
                $xml_time = $xml_timetracking->addChild('time');
                $xml_time->addAttribute('artifact_id', (string) $issue_representation->getId());

                $user_time = $this->jira_user_retriever->retrieveJiraAuthor(
                    $worklog->getAuthor()
                );

                $this->cdata_factory->insertWithAttributes(
                    $xml_time,
                    'user',
                    $user_time->getUserName(),
                    ['format' => 'username']
                );

                $xml_time->addChild('minutes', (string) ceil($worklog->getSeconds() / 60));

                $this->cdata_factory->insertWithAttributes(
                    $xml_time,
                    'day',
                    date('c', $worklog->getStartDate()->getTimestamp()),
                    ['format' => 'ISO8601']
                );

                $worklog_comment = $worklog->getComment();
                if ($worklog_comment !== '') {
                    $this->cdata_factory->insert(
                        $xml_time,
                        'step',
                        $this->getTimeStepTextContent(
                            $worklog_comment,
                            $worklog->getAuthor(),
                            $user_time
                        )
                    );
                }
            }
        }
    }

    private function getTimeStepTextContent(
        string $worklog_comment,
        JiraUser $comment_author,
        PFUser $user_time,
    ): string {
        $step_content = '';
        if ((int) $user_time->getId() === TrackerImporterUser::ID) {
            $step_content = "Time added by " . $comment_author->getDisplayName() . " | ";
        }

        $step_content .= $worklog_comment;

        return $step_content;
    }
}
