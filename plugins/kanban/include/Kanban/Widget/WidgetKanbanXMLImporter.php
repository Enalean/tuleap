<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\Widget;

use Tuleap\Widget\Event\ConfigureAtXMLImport;
use Tuleap\XML\MappingsRegistry;

final class WidgetKanbanXMLImporter
{
    /**
     * @throws \RuntimeException
     */
    public function configureWidget(ConfigureAtXMLImport $event): void
    {
        $content_id = $event->getWidget()->create($this->getRequest($event));
        $event->setContentId($content_id);
        $event->setWidgetIsConfigured();
    }

    /**
     * @throws \RuntimeException
     */
    private function getRequest(ConfigureAtXMLImport $event): \Codendi_Request
    {
        return new \Codendi_Request(
            $this->getParametersFromXML($event->getXML(), $event->getMappingsRegistry())
        );
    }

    /**
     * @throws \RuntimeException
     * @return string[][]
     */
    private function getParametersFromXML(\SimpleXMLElement $xml, MappingsRegistry $mapping_registry): array
    {
        $params = [
            'kanban' => [
                'title' => '',
            ],
        ];
        if (isset($xml->preference)) {
            foreach ($xml->preference as $preference) {
                $preference_name = trim((string) $preference['name']);
                $this->setNameReferenceParameters($preference, $mapping_registry, $preference_name, $params);
            }
        }
        if (! isset($params['kanban']['id'])) {
            throw new \RuntimeException("Kanban id is missing");
        }
        return $params;
    }

    /**
     * @throws \RuntimeException
     */
    private function setNameReferenceParameters(
        \SimpleXMLElement $preference,
        MappingsRegistry $mapping_registry,
        string $preference_name,
        array &$params,
    ): void {
        foreach ($preference->reference as $reference) {
            $key    = trim((string) $reference['name']);
            $ref    = trim((string) $reference['REF']);
            $kanban = $mapping_registry->getReference($ref);
            if ($kanban === null) {
                throw new \RuntimeException("Reference $ref for kanban widget was not found");
            }
            $params[$preference_name][$key] = $kanban->getId();
        }
    }
}
