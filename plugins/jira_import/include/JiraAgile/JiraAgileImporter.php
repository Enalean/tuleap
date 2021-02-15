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
use Tuleap\Tracker\XML\IDGenerator;

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

    public function exportScrum(LoggerInterface $logger, \SimpleXMLElement $project, string $jira_project, IDGenerator $id_generator): void
    {
        $board = $this->boards_retriever->getFirstScrumBoardForProject($jira_project);
        if ($board) {
            $logger->info('Project has Agile configuration to import');

            $scrum_tracker_builder = new ScrumTrackerBuilder();
            $scrum_tracker_builder->export($project, $id_generator);

            $sprints = $this->sprint_retriever->getAllSprints($board);
            foreach ($sprints as $sprint) {
                $logger->debug('Got sprint ' . $sprint->name . ' ' . $sprint->url);
            }
        }
    }
}
