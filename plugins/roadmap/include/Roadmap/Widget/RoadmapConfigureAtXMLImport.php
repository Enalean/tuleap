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
            'roadmap' => [
                'tracker_id' => '',
                'title'      => ''
            ],
        ];
        if (! isset($xml->preference)) {
            throw new \RuntimeException("Widget Roadmap does not have a preference node xml");
        }
        $this->setTrackerDataFromPreference($xml->preference[0], $mapping_registry, $params);

        return $params;
    }

    private function setTrackerDataFromPreference(\SimpleXMLElement $xml, MappingsRegistry $mapping_registry, array &$params): void
    {
        $reference_node =  $xml->xpath("reference[@name='tracker_id']");
        if (count($reference_node) === 0) {
            throw new \RuntimeException("Reference tracker_id is not found in xml");
        }
        $ref = (string) $reference_node[0]['REF'];

        $tracker = $mapping_registry->getReference($ref);
        if ($tracker === null) {
            throw new \RuntimeException("Reference tracker_id for roadmap widget was not found");
        }

        $title_node = $xml->xpath("reference[@name='title']");
        if (count($title_node) === 0) {
            $title = 'Roadmap';
        } else {
            $title = (string) $title_node[0]['REF'];
        }

        $params['roadmap']['tracker_id'] = $tracker;
        $params['roadmap']['title']      = $title;
    }
}
