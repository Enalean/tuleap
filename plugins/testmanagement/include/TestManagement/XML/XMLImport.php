<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\XML;

use Exception;
use Project;
use Tuleap\TestManagement\Administration\TrackerChecker;
use Tuleap\TestManagement\Administration\TrackerHasAtLeastOneFrozenFieldsPostActionException;
use Tuleap\TestManagement\Administration\TrackerHasAtLeastOneHiddenFieldsetsPostActionException;
use Tuleap\TestManagement\Administration\TrackerNotInProjectException;
use Tuleap\TestManagement\Config;
use XML_RNGValidator;
use SimpleXMLElement;

class XMLImport
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
     * @var TrackerChecker
     */
    private $tracker_checker;

    public function __construct(Config $config, TrackerChecker $tracker_checker)
    {
        $this->config          = $config;
        $this->tracker_checker = $tracker_checker;
    }

    /**
     * @throws Exception
     */
    public function import(Project $project, string $extraction_path, array $tracker_mapping): void
    {
        $xml_path = $extraction_path . '/testmanagement.xml';
        if (file_exists($xml_path)) {
            $xml = simplexml_load_string(file_get_contents($xml_path));
            if (! $xml) {
                throw new Exception("Cannot load XML from $xml_path");
            }

            $xml_validator = new XML_RNGValidator();
            $rng_path      = realpath(TESTMANAGEMENT_RESOURCE_DIR . '/testmanagement.rng');

            $xml_validator->validate($xml, $rng_path);

            try {
                $campaign_tracker_id   = $this->getXMLRef($xml, $tracker_mapping, self::CAMPAIGNS);
                $definition_tracker_id = $this->getXMLRef($xml, $tracker_mapping, self::DEFINITIONS);
                $execution_tracker_id  = $this->getXMLRef($xml, $tracker_mapping, self::EXECUTIONS);
                $issue_tracker_id      = $this->getXMLRef($xml, $tracker_mapping, self::ISSUES);

                if ($issue_tracker_id !== '') {
                    $this->tracker_checker->checkTrackerIsInProject($project, $issue_tracker_id);
                }

                if ($campaign_tracker_id !== '' && $definition_tracker_id !== '' && $execution_tracker_id !== '') {
                    $this->tracker_checker->checkTrackerIsInProject($project, $campaign_tracker_id);
                    $this->tracker_checker->checkSubmittedTrackerCanBeUsed($project, $definition_tracker_id);
                    $this->tracker_checker->checkSubmittedTrackerCanBeUsed($project, $execution_tracker_id);

                    $this->config->setProjectConfiguration(
                        $project,
                        $campaign_tracker_id,
                        $definition_tracker_id,
                        $execution_tracker_id,
                        $issue_tracker_id === '' ? null : (int) $issue_tracker_id
                    );
                }
            } catch (
                TrackerNotInProjectException |
                TrackerHasAtLeastOneFrozenFieldsPostActionException |
                TrackerHasAtLeastOneHiddenFieldsetsPostActionException $exception
            ) {
                throw new Exception("Trackers defined in the configuration files are not valid.");
            }
        }
    }

    /**
     * @return mixed|string
     */
    private function getXMLRef(SimpleXMLElement $xml, array $tracker_mapping, string $tracker_name)
    {
        $reference = (string) $xml->configuration->{$tracker_name};

        if (! isset($tracker_mapping[$reference])) {
            return '';
        }

        return $tracker_mapping[$reference];
    }
}
