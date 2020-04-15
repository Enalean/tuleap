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
use Tracker_XML_Exporter_ChangesetXMLExporter;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\Config;
use XML_RNGValidator;

class Exporter
{
    // Those constants maps testmanagement.rnc
    public const ISSUES      = 'issues';
    public const CAMPAIGNS   = 'campaigns';
    public const DEFINITIONS = 'definitions';
    public const EXECUTIONS  = 'executions';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var XML_RNGValidator
     */
    private $validator;
    /**
     * @var ExecutionDao
     */
    private $execution_dao;

    public function __construct(Config $config, XML_RNGValidator $validator, ExecutionDao $execution_dao)
    {
        $this->config        = $config;
        $this->validator     = $validator;
        $this->execution_dao = $execution_dao;
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
        if ($issue_tracker_id) {
            $configuration_xml_content->addChild(self::ISSUES, "T$issue_tracker_id");
        }

        $campaign_tracker_id = $this->config->getCampaignTrackerId($project);
        $configuration_xml_content->addChild(self::CAMPAIGNS, "T$campaign_tracker_id");

        $definition_tracker_id = $this->config->getTestDefinitionTrackerId($project);
        $configuration_xml_content->addChild(self::DEFINITIONS, "T$definition_tracker_id");

        $execution_tracker_id = $this->config->getTestExecutionTrackerId($project);
        $configuration_xml_content->addChild(self::EXECUTIONS, "T$execution_tracker_id");

        if ($execution_tracker_id) {
            $this->addExecutionsNode($testmanagement_xml_content, $execution_tracker_id);
        }

        $this->validateXMLContent($testmanagement_xml_content);

        return $testmanagement_xml_content;
    }

    /**
     * @param $testmanagement_xml_content
     *
     */
    private function validateXMLContent(SimpleXMLElement $testmanagement_xml_content): void
    {
        $rng_path = realpath(TESTMANAGEMENT_RESOURCE_DIR . '/testmanagement.rng');

        $this->validator->validate($testmanagement_xml_content, $rng_path);
    }

    private function addExecutionsNode(SimpleXMLElement $testmanagement_xml_content, int $execution_tracker_id): void
    {
        $executions = $this->execution_dao->searchByExecutionTrackerId($execution_tracker_id);
        if (count($executions) === 0) {
            return;
        }

        $executions_xml = $testmanagement_xml_content->addChild('executions');
        foreach ($executions as $row) {
            $child = $executions_xml->addChild('execution');
            $child->addAttribute('execution_artifact_id', $row['execution_artifact_id']);
            $child->addAttribute('definition_changeset_id', Tracker_XML_Exporter_ChangesetXMLExporter::PREFIX . $row['definition_changeset_id']);
        }
    }
}
