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
 *
 */

namespace Tracker\Artifact;

use Logger;
use SimpleXMLElement;
use Tuleap\Project\XML\Import\ImportConfig;

class XMLArtifactSourcePlatformExtractor
{
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function getSourcePlatform(SimpleXMLElement $xml_artifacts, ImportConfig $config)
    {
        if (! isset($xml_artifacts->attributes()['source_platform'])) {
            if ($config->isUpdate()) {
                $this->logger->warn("No attribute source_platform in XML. New artifact created.");
            }
            return null;
        }

        if (! filter_var($xml_artifacts->attributes()['source_platform'], FILTER_VALIDATE_URL)) {
            if ($config->isUpdate()) {
                $this->logger->warn("Source platform is not a valid URL. New artifact created.");
            }
            return null;
        }

        return $xml_artifacts->attributes()['source_platform'];
    }
}
