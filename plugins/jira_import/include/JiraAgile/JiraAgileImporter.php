<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\JiraImport\JiraAgile;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Artifact\Changeset\XML\XMLChangeset;
use Tuleap\Tracker\Artifact\XML\XMLArtifact;
use Tuleap\Tracker\Creation\JiraImporter\IssueType;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\XML\XMLArtifactLinkChangesetValue;
use Tuleap\Tracker\FormElement\Field\Date\XML\XMLDateChangesetValue;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticChangesetValue;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceByLabel;
use Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringChangesetValue;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\XML\XMLUser;

final class JiraAgileImporter
{
    /**
     * @var JiraBoardsRetriever
     */
    private $boards_retriever;
    /**
     * @var JiraSprintRetriever
     */
    private $sprint_retriever;
    /**
     * @var JiraSprintIssuesRetriever
     */
    private $sprint_issues_retriever;
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(JiraBoardsRetriever $boards_retriever, JiraSprintRetriever $sprint_retriever, JiraSprintIssuesRetriever $sprint_issues_retriever, EventDispatcherInterface $event_dispatcher)
    {
        $this->boards_retriever        = $boards_retriever;
        $this->sprint_retriever        = $sprint_retriever;
        $this->sprint_issues_retriever = $sprint_issues_retriever;
        $this->event_dispatcher        = $event_dispatcher;
    }

    /**
     * @param IssueType[] $jira_issue_types
     */
    public function exportScrum(
        LoggerInterface $logger,
        \SimpleXMLElement $project,
        string $jira_project,
        IDGenerator $id_generator,
        \PFUser $import_user,
        array $jira_issue_types,
        string $jira_epic_issue_type
    ): void {
        $board = $this->boards_retriever->getFirstScrumBoardForProject($jira_project);
        if (! $board) {
            return;
        }
        $logger->info('Project has Agile configuration to import');

        $scrum_tracker_builder = new ScrumTrackerBuilder($this->event_dispatcher);
        $scrum_tracker         = $scrum_tracker_builder->get($id_generator);

        $sprints = $this->sprint_retriever->getAllSprints($board);
        foreach ($sprints as $sprint) {
            $logger->debug('Create sprint ' . $sprint->name);

            $changeset = (new XMLChangeset(XMLUser::buildUsername($import_user->getUserName()), new \DateTimeImmutable()))
                ->withFieldChange(new XMLStringChangesetValue(ScrumTrackerBuilder::NAME_FIELD_NAME, $sprint->name))
                ->withFieldChange(new XMLBindStaticChangesetValue(ScrumTrackerBuilder::STATUS_FIELD_NAME, [new XMLBindValueReferenceByLabel(ScrumTrackerBuilder::STATUS_FIELD_NAME, $sprint->state)]));

            if ($sprint->start_date !== null) {
                $changeset = $changeset->withFieldChange(new XMLDateChangesetValue(ScrumTrackerBuilder::START_DATE_FIELD_NAME, $sprint->start_date));
            }

            if ($sprint->end_date !== null) {
                $changeset = $changeset->withFieldChange(new XMLDateChangesetValue(ScrumTrackerBuilder::END_DATE_FIELD_NAME, $sprint->end_date));
            }

            if ($sprint->complete_date !== null) {
                $changeset = $changeset->withFieldChange(new XMLDateChangesetValue(ScrumTrackerBuilder::COMPLETED_DATE_FIELD_NAME, $sprint->complete_date));
            }

            $links = $this->sprint_issues_retriever->getArtifactLinkChange($sprint);
            if (count($links)) {
                $changeset = $changeset->withFieldChange(new XMLArtifactLinkChangesetValue(ScrumTrackerBuilder::ARTIFACT_LINK_FIELD_NAME, $links));
            }

            $scrum_tracker = $scrum_tracker->withArtifact(
                (new XMLArtifact($id_generator->getNextId()))
                    ->withChangeset($changeset)
            );
        }

        $scrum_tracker->export($project->trackers);

        $this->exportPlanningConfiguration($logger, $project, $scrum_tracker, $jira_issue_types, $jira_epic_issue_type);
    }

    private function exportPlanningConfiguration(
        LoggerInterface $logger,
        \SimpleXMLElement $project,
        \Tuleap\Tracker\XML\XMLTracker $scrum_tracker,
        array $jira_issue_types,
        string $jira_epic_issue_type
    ): void {
        $logger->debug("Export agiledashboard planning configuration");

        $xml_planning = $project->addChild('agiledashboard')->addChild('plannings')->addChild('planning');
        $xml_planning->addAttribute("name", "Sprint plan");
        $xml_planning->addAttribute("plan_title", "Sprint plan");
        $xml_planning->addAttribute("planning_tracker_id", $scrum_tracker->getId());
        $xml_planning->addAttribute("backlog_title", "Backlog");

        $xml_backlogs = $xml_planning->addChild("backlogs");
        foreach ($jira_issue_types as $issue_type) {
            if ($issue_type->isSubtask() === true || $issue_type->getName() === $jira_epic_issue_type) {
                continue;
            }

            $xml_backlogs->addChild("backlog", $issue_type->getId());
        }
    }
}
