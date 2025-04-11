<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\CrossTracker\Widget;

use Codendi_Request;
use Tuleap\Project\XML\Import\ImportNotValidException;
use Tuleap\Widget\Event\ConfigureAtXMLImport;

final class WidgetCrossTrackerXMLImporter
{
    /**
     * @throws ImportNotValidException
     */
    public function configureWidget(ConfigureAtXMLImport $event): void
    {
        $content_id = $event->getWidget()->create($this->getRequest($event));
        $event->setContentId($content_id);
        $event->setWidgetIsConfigured();
    }

    /**
     * @throws ImportNotValidException
     */
    private function getRequest(ConfigureAtXMLImport $event): Codendi_Request
    {
        return new Codendi_Request(
            $this->getParametersFromXML($event->getXML())
        );
    }

    /**
     * @throws ImportNotValidException
     */
    private function getParametersFromXML(\SimpleXMLElement $xml): array
    {
        $parameters              = [
            'queries' => [],
        ];
        $number_of_default_query = 0;
        foreach ($xml->preference as $query_node) {
            $is_default = $this->getQueryIsDefaultFromXML($query_node);

            if ($is_default) {
                $number_of_default_query++;
            }

            if ($number_of_default_query > 1) {
                throw new ImportNotValidException('There are more than one default query set');
            }
            $parameters['queries'][] = [
                'title'       => $this->getQueryTitleFromXML($query_node),
                'description' => $this->getQueryDescriptionFromXML($query_node),
                'tql'         => $this->getQueryTqlFromXML($query_node),
                'is_default'  => $is_default,
            ];
        }
        return $parameters;
    }

    /**
     * @throws ImportNotValidException
     */
    private function getQueryTitleFromXML(\SimpleXMLElement $xml): string
    {
        $title_node = $xml->xpath("value[@name='title']");
        return isset($title_node) && count($title_node) > 0 && (string) $title_node[0] !== '' ? (string) $title_node[0] : throw new ImportNotValidException("'Title' value element is empty or missing");
    }

    /**
     * @throws ImportNotValidException
     */
    private function getQueryDescriptionFromXML(\SimpleXMLElement $xml): string
    {
        $description_node = $xml->xpath("value[@name='description']");

        return isset($description_node) && count($description_node) > 0  ? (string) $description_node[0] : throw new ImportNotValidException("'Description' value element is missing");
    }

    /**
     * @throws ImportNotValidException
     */
    private function getQueryTqlFromXML(\SimpleXMLElement $xml): string
    {
        $tql_node = $xml->xpath("value[@name='tql']");

        return isset($tql_node) && count($tql_node) > 0 && (string) $tql_node[0] !== '' ? (string) $tql_node[0] : throw new ImportNotValidException("'Tql' value element is empty or missing");
    }

    /**
     * @throws ImportNotValidException
     */
    private function getQueryIsDefaultFromXML(\SimpleXMLElement $xml): bool
    {
        $is_default_nodes = $xml->xpath("value[@name='is-default']");

        return isset($is_default_nodes) && count($is_default_nodes) > 0 ? (string) $is_default_nodes[0] === '1' : throw new ImportNotValidException("'Is default' value element is empty or missing");
    }
}
