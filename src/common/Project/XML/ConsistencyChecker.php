<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\XML;

use ServiceManager;

class ConsistencyChecker
{
    /**
     * @var ServiceManager
     */
    private $service_manager;
    /**
     * @var XMLFileContentRetriever
     */
    private $xml_file_content_retriever;

    public function __construct(ServiceManager $service_manager, XMLFileContentRetriever $xml_file_content_retriever)
    {
        $this->service_manager            = $service_manager;
        $this->xml_file_content_retriever = $xml_file_content_retriever;
    }

    public function areAllServicesAvailable(string $file_path): bool
    {
        if (! is_file($file_path)) {
            throw new \RuntimeException('Invalid file path provided');
        }
        $xml = $this->xml_file_content_retriever->getSimpleXMLElementFromFilePath($file_path);

        $available_services = [];
        foreach ($this->service_manager->getListOfServicesAvailableAtSiteLevel() as $service) {
            $available_services[$service->getShortName()] = true;
        }

        foreach ($xml->services->service as $service) {
            if ((string) $service['enabled'] === 'true') {
                $service_name = (string) $service['shortname'];
                if (! isset($available_services[$service_name])) {
                    return false;
                }
            }
        }
        return true;
    }
}
