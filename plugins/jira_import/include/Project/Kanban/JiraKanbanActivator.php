<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\JiraImport\Project\Kanban;

use LogicException;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\Import\JiraAllIssuesMonoTrackersInXmlExporter;

final class JiraKanbanActivator
{
    private const DEFAULT_XML_KANBAN_ID = "K01";

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function activateKanbanForProject(\SimpleXMLElement $project_xml): void
    {
        $this->logger->info('Project has Kanban to activate');

        $xml_agiledashboard = $project_xml->addChild('agiledashboard');
        if ($xml_agiledashboard === null) {
            throw new LogicException();
        }

        $xml_kanban_list = $xml_agiledashboard->addChild("kanban_list");
        if ($xml_kanban_list === null) {
            throw new LogicException();
        }

        $xml_kanban_list->addAttribute('title', 'Kanban');
        $xml_kanban = $xml_kanban_list->addChild("kanban");
        if ($xml_kanban === null) {
            throw new LogicException();
        }

        $xml_kanban->addAttribute("tracker_id", JiraAllIssuesMonoTrackersInXmlExporter::MONO_TRACKER_XML_ID);
        $xml_kanban->addAttribute("name", JiraAllIssuesMonoTrackersInXmlExporter::MONO_TRACKER_NAME);
        $xml_kanban->addAttribute("ID", self::DEFAULT_XML_KANBAN_ID);
    }
}
