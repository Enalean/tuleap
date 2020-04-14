<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class AgileDashboard_XMLFullStructureExporter
{

    /** @var EventManager */
    private $event_manager;

    /** @var AgileDashboardRouterBuilder */
    private $router_builder;

    public function __construct(
        EventManager $event_manager,
        AgileDashboardRouterBuilder $builder
    ) {
        $this->event_manager  = $event_manager;
        $this->router_builder = $builder;
    }

    /**
     *
     * @return string A full XML document string
     */
    public function export(Project $project)
    {
        $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <project />');

        $request = $this->buildRequest($project, $xml_element);
        $router  = $this->router_builder->build($request);
        $router->route($request);

        $this->exportOtherPlugins($project, $xml_element);

        return $this->convertToXml($xml_element);
    }

    /**
     * @return Codendi_Request
     */
    private function buildRequest(Project $project, SimpleXMLElement $xml_element)
    {
        $params['action']     = 'export';
        $params['project_id'] = $project->getID();
        $params['group_id']   = $project->getID();
        $params['into_xml']   = $xml_element;

        return new Codendi_Request($params);
    }

    /**
     * @return SimpleXMLElement
     */
    private function exportOtherPlugins(Project $project, SimpleXMLElement $into_xml)
    {
        $this->event_manager->processEvent(
            AGILEDASHBOARD_EXPORT_XML,
            array(
                'project'  => $project,
                'into_xml' => $into_xml
            )
        );
    }

    /**
     *
     * @return String
     */
    private function convertToXml(SimpleXMLElement $xml_element)
    {
        $dom = dom_import_simplexml($xml_element)->ownerDocument;
        if ($dom === null) {
            return '';
        }
        $dom->formatOutput = true;

        return $dom->saveXML();
    }
}
