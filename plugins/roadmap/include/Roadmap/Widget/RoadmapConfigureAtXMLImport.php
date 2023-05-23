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
     * @psalm-return array{roadmap: array{tracker_ids: string[], filter_report_id: ?string, title: string, default_timescale: string, lvl1_iteration_tracker_id?: string, lvl2_iteration_tracker_id?: string}}
     */
    private function getParametersFromXML(\SimpleXMLElement $xml, MappingsRegistry $mapping_registry): array
    {
        $params = [
            'tracker_ids'       => $this->getReferencedTrackerIdsFromXML($xml, $mapping_registry),
            'filter_report_id'  => $this->getReferencedReportIdFromXML($xml, $mapping_registry, 'filter_report_id'),
            'title'             => $this->getWidgetTitleFromXML($xml),
            'default_timescale' => $this->getDefaultTimescaleFromXML($xml),
        ];

        foreach (['lvl1_iteration_tracker_id', 'lvl2_iteration_tracker_id'] as $iteration_tracker_name) {
            $iteration_tracker_id = $this->getReferencedIterationTrackerIdFromXML(
                $xml,
                $mapping_registry,
                $iteration_tracker_name,
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

    private function getDefaultTimescaleFromXML(\SimpleXMLElement $xml): string
    {
        $timescale_nodes = $xml->xpath("preference/value[@name='default-timescale']");

        return count($timescale_nodes) > 0 ? (string) $timescale_nodes[0] : RoadmapProjectWidget::DEFAULT_TIMESCALE_MONTH;
    }

    /**
     * @return string[]
     */
    private function getReferencedTrackerIdsFromXML(\SimpleXMLElement $xml, MappingsRegistry $mapping_registry): array
    {
        $tracker_id_nodes = $xml->xpath("preference/value[@name='tracker_id']");
        if (count($tracker_id_nodes) === 0) {
            throw new \RuntimeException("Reference tracker_id for roadmap widget was not found");
        }

        $imported_tracker_ids = [];
        foreach ($tracker_id_nodes as $tracker_id_node) {
            $ref = (string) $tracker_id_node;

            $imported_tracker_id = $mapping_registry->getReference($ref);
            if ($imported_tracker_id === null) {
                throw new \RuntimeException("Reference tracker_id for roadmap widget was not found");
            }

            $imported_tracker_ids[] = (string) $imported_tracker_id;
        }

        return $imported_tracker_ids;
    }

    private function getReferencedIterationTrackerIdFromXML(
        \SimpleXMLElement $xml,
        MappingsRegistry $mapping_registry,
        string $name,
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

    private function getReferencedReportIdFromXML(
        \SimpleXMLElement $xml,
        MappingsRegistry $mapping_registry,
        string $name,
    ): ?string {
        $matching_nodes = $xml->xpath("preference/value[@name='$name']");
        if ($matching_nodes === null || count($matching_nodes) === 0) {
            return null;
        }
        $ref = (string) $matching_nodes[0];

        $reference = $mapping_registry->getReference($ref);
        if ($reference === null) {
            throw new \RuntimeException("Reference $name for roadmap widget was not found");
        }

        return (string) $reference->getId();
    }
}
