<?php
/**
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

namespace Tuleap\Roadmap\Widget;

use Codendi_Request;
use Tuleap\Roadmap\RoadmapProjectWidget;
use Tuleap\Widget\Event\ConfigureAtXMLImport;
use Tuleap\XML\MappingsRegistry;

final class RoadmapConfigureAtXMLImport
{
    public function configure(ConfigureAtXMLImport $event): void
    {
        if ($event->getWidget()->getId() !== RoadmapProjectWidget::ID) {
            return;
        }
        $content_id = $event->getWidget()->create($this->getRequest($event));
        $event->setContentId($content_id);
        $event->setWidgetIsConfigured();
    }


    private function getRequest(ConfigureAtXMLImport $event): Codendi_Request
    {
        return new Codendi_Request(
            $this->getParametersFromXML($event->getXML(), $event->getMappingsRegistry())
        );
    }

    /**
     * @return string[][]
     */
    private function getParametersFromXML(\SimpleXMLElement $xml, MappingsRegistry $mapping_registry): array
    {
        $params = [
            'tracker_id' => $this->getReferencedTrackerIdFromXML($xml, $mapping_registry),
            'title'      => $this->getWidgetTitleFromXML($xml),
        ];

        foreach (['lvl1_iteration_tracker_id', 'lvl2_iteration_tracker_id'] as $iteration_tracker_name) {
            $iteration_tracker_id = $this->getReferencedIterationTrackerIdFromXML(
                $xml,
                $mapping_registry,
                $iteration_tracker_name
            );
            if ($iteration_tracker_id) {
                $params[$iteration_tracker_name] = $iteration_tracker_id;
            }
        }

        return [
            'roadmap' => $params,
        ];
    }

    private function getWidgetTitleFromXML(\SimpleXMLElement $xml): string
    {
        $title_nodes = $xml->xpath("preference/value[@name='title']");

        return count($title_nodes) > 0 ? (string) $title_nodes[0] : 'Roadmap';
    }

    private function getReferencedTrackerIdFromXML(\SimpleXMLElement $xml, MappingsRegistry $mapping_registry): string
    {
        $tracker_id_nodes = $xml->xpath("preference/value[@name='tracker_id']");
        if (count($tracker_id_nodes) === 0) {
            throw new \RuntimeException("Reference tracker_id for roadmap widget was not found");
        }
        $ref = (string) $tracker_id_nodes[0];

        $imported_tracker_id = $mapping_registry->getReference($ref);
        if ($imported_tracker_id === null) {
            throw new \RuntimeException("Reference tracker_id for roadmap widget was not found");
        }

        return (string) $imported_tracker_id;
    }

    private function getReferencedIterationTrackerIdFromXML(
        \SimpleXMLElement $xml,
        MappingsRegistry $mapping_registry,
        string $name
    ): ?string {
        $tracker_id_nodes = $xml->xpath("preference/value[@name='$name']");
        if (count($tracker_id_nodes) === 0) {
            return null;
        }
        $ref = (string) $tracker_id_nodes[0];

        $imported_tracker_id = $mapping_registry->getReference($ref);
        if ($imported_tracker_id === null) {
            throw new \RuntimeException("Reference $name for roadmap widget was not found");
        }

        return (string) $imported_tracker_id;
    }
}
