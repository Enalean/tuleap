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

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Artifact\Changeset\XML\XMLChangeset;
use Tuleap\Tracker\Artifact\XML\XMLArtifact;
use Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringValue;
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

    public function __construct(JiraBoardsRetriever $boards_retriever, JiraSprintRetriever $sprint_retriever)
    {
        $this->boards_retriever = $boards_retriever;
        $this->sprint_retriever = $sprint_retriever;
    }

    public function exportScrum(LoggerInterface $logger, \SimpleXMLElement $project, string $jira_project, IDGenerator $id_generator, \PFUser $import_user): void
    {
        $board = $this->boards_retriever->getFirstScrumBoardForProject($jira_project);
        if (! $board) {
            return;
        }
        $logger->info('Project has Agile configuration to import');

        $scrum_tracker_builder = new ScrumTrackerBuilder();
        $scrum_tracker         = $scrum_tracker_builder->get($id_generator);

        $sprints = $this->sprint_retriever->getAllSprints($board);
        foreach ($sprints as $sprint) {
            $logger->debug('Create sprint ' . $sprint->name);
            $scrum_tracker = $scrum_tracker->withArtifact(
                (new XMLArtifact($id_generator->getNextId()))
                    ->withChangeset(
                        (new XMLChangeset(XMLUser::buildUsername($import_user->getUserName()), new \DateTimeImmutable()))
                            ->withFieldChange(new XMLStringValue(ScrumTrackerBuilder::NAME_FIELD_NAME, $sprint->name))
                    )
            );
        }

        $scrum_tracker->export($project->trackers);
    }
}
