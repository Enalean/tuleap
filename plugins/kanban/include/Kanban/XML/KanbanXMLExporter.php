<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Kanban\XML;

use Tuleap\Kanban\KanbanFactory;
use Project;
use SimpleXMLElement;
use Tuleap\Kanban\Legacy\LegacyKanbanRetriever;

class KanbanXMLExporter
{
    public const NODE_KANBAN_LST = "kanban_list";
    public const NODE_KANBAN     = "kanban";

    public const TRACKER_ID_PREFIX = 'T';
    public const KANBAN_ID_PREFIX  = 'K';

    public function __construct(
        private readonly LegacyKanbanRetriever $configuration_dao,
        private readonly KanbanFactory $kanban_factory,
    ) {
    }

    /**
     * @throws \Tuleap\Kanban\SemanticStatusNotFoundException
     */
    public function export(SimpleXMLElement $xml_element, Project $project): void
    {
        if (! $this->configuration_dao->isKanbanActivated((int) $project->getID())) {
            return;
        }

        $kanban_list_node = $xml_element->addChild(self::NODE_KANBAN_LST);
        if ($kanban_list_node === null) {
            throw new \Exception("Unable to create kanban_list node");
        }

        $kanban_tracker_ids = $this->kanban_factory->getKanbanTrackerIds((int) $project->getID());
        foreach ($kanban_tracker_ids as $tracker_id) {
            $kanban = $this->kanban_factory->getKanbanByTrackerId($tracker_id);

            if ($kanban === null) {
                continue;
            }

            $kanban_node = $kanban_list_node->addChild(self::NODE_KANBAN);
            if ($kanban_node === null) {
                throw new \Exception("Unable to create kanban node");
            }
            $kanban_node->addAttribute('tracker_id', $this->getFormattedTrackerId($tracker_id));
            $kanban_node->addAttribute('name', $kanban->getName());
            $kanban_node->addAttribute('ID', $this->getFormattedKanbanId($kanban->getId()));
        }
    }

    private function getFormattedTrackerId(int $tracker_id): string
    {
        return self::TRACKER_ID_PREFIX . $tracker_id;
    }

    private function getFormattedKanbanId(int $kanban_id): string
    {
        return self::KANBAN_ID_PREFIX . $kanban_id;
    }
}
