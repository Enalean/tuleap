<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\TestManagement\XML;

use Project;
use SimpleXMLElement;
use Tuleap\TestManagement\Config;
use XML_RNGValidator;

class Exporter
{
    // Those constants maps testmanagement.rnc
    const ISSUES      = 'issues';
    const CAMPAIGNS   = 'campaigns';
    const DEFINITIONS = 'definitions';
    const EXECUTIONS  = 'executions';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var XML_RNGValidator
     */
    private $validator;

    public function __construct(Config $config, XML_RNGValidator $validator)
    {
        $this->config    = $config;
        $this->validator = $validator;
    }

    /**
     * @return null|SimpleXMLElement
     */
    public function exportToXML(Project $project)
    {
        if ($this->config->isConfigNeeded($project)) {
            return null;
        }

        $testmanagement_xml_content = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <testmanagement />'
        );

        $configuration_xml_content = $testmanagement_xml_content->addChild('configuration');

        $issue_tracker_id = $this->config->getIssueTrackerId($project);
        $configuration_xml_content->addChild(self::ISSUES, "T$issue_tracker_id");

        $campaign_tracker_id = $this->config->getCampaignTrackerId($project);
        $configuration_xml_content->addChild(self::CAMPAIGNS, "T$campaign_tracker_id");

        $definition_tracker_id = $this->config->getTestDefinitionTrackerId($project);
        $configuration_xml_content->addChild(self::DEFINITIONS, "T$definition_tracker_id");

        $execution_tracker_id = $this->config->getTestExecutionTrackerId($project);
        $configuration_xml_content->addChild(self::EXECUTIONS, "T$execution_tracker_id");

        $this->validateXMLContent($testmanagement_xml_content);

        return $testmanagement_xml_content;
    }

    /**
     * @param $testmanagement_xml_content
     */
    private function validateXMLContent(SimpleXMLElement $testmanagement_xml_content)
    {
        $rng_path = realpath(TESTMANAGEMENT_RESOURCE_DIR.'/testmanagement.rng');

        $this->validator->validate($testmanagement_xml_content, $rng_path);
    }
}
