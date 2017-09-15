<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use Project;
use XML_RNGValidator;
use SimpleXMLElement;

class XMLImport
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

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function import(Project $project, $extraction_path, $tracker_mapping)
    {
        $xml_path = $extraction_path .'/testmanagement.xml';
        if (file_exists($xml_path)) {
            $xml = simplexml_load_string(file_get_contents($xml_path));
            if (! $xml) {
                throw new Exception("Cannot load XML from $xml_path");
            }

            $xml_validator = new XML_RNGValidator();
            $rng_path      = realpath(TESTMANAGEMENT_RESOURCE_DIR.'/testmanagement.rng');

            $xml_validator->validate($xml, $rng_path);

            $this->config->setProjectConfiguration(
                $project,
                $this->getXMLRef($xml, $tracker_mapping, self::CAMPAIGNS),
                $this->getXMLRef($xml, $tracker_mapping, self::DEFINITIONS),
                $this->getXMLRef($xml, $tracker_mapping, self::EXECUTIONS),
                $this->getXMLRef($xml, $tracker_mapping, self::ISSUES)
            );
        }
    }

    public function getXMLRef(SimpleXMLElement $xml, $tracker_mapping, $tracker_name)
    {
        $reference = (string) $xml->configuration->{$tracker_name};
        return $tracker_mapping[$reference];
    }
}
