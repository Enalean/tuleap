<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 */

namespace Tuleap\Kanban\XML;

use Tuleap\Kanban\KanbanColumnFactory;
use Tuleap\Kanban\KanbanColumnManager;
use Tuleap\Kanban\KanbanFactory;
use Tuleap\Kanban\KanbanManager;
use Psr\Log\LoggerInterface;
use PFUser;
use SimpleXMLElement;
use TrackerXmlFieldsMapping;
use Tuleap\XML\MappingsRegistry;

final class KanbanXmlImporter
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly KanbanManager $kanban_manager,
        private readonly KanbanColumnManager $dashboard_kanban_column_manager,
        private readonly KanbanFactory $dashboard_kanban_factory,
        private readonly KanbanColumnFactory $dashboard_kanban_column_factory,
    ) {
    }

    public function import(
        SimpleXMLElement $xml,
        array $tracker_mapping,
        TrackerXmlFieldsMapping $field_mapping,
        PFUser $user,
        MappingsRegistry $mappings_registry,
    ): void {
        if (! $xml->agiledashboard->kanban_list) {
            $this->logger->info('0 Kanban found');

            return;
        }

        foreach ($xml->agiledashboard->kanban_list->kanban as $xml_configuration) {
            $attrs = $xml_configuration->attributes();
            $this->logger->info('Creating kanban ' . $attrs['name']);

            $kanban_id = $this->kanban_manager->createKanban(
                (string) $attrs['name'],
                (bool) $attrs['is_promoted'],
                $tracker_mapping[(string) $attrs['tracker_id']],
            );

            $kanban = $this->dashboard_kanban_factory->getKanbanForXmlImport(
                $kanban_id
            );
            $mappings_registry->addReference((string) $attrs['ID'], $kanban);

            foreach ($xml_configuration as $xml_columns) {
                $columns_attrs = $xml_columns->attributes();
                $column        = $this->dashboard_kanban_column_factory->getColumnForAKanban(
                    $kanban,
                    $field_mapping->getNewOpenValueId((string) $columns_attrs['REF']),
                    $user
                );

                $this->logger->info('Updating WIP to ' . $attrs['REF']);
                $this->dashboard_kanban_column_manager->updateWipLimit(
                    $user,
                    $kanban,
                    $column,
                    (int) $columns_attrs['wip']
                );
            }
        }
    }
}
