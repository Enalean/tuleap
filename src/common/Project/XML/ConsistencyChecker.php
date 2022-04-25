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

use Tuleap\XML\PHPCast;

class ConsistencyChecker
{
    public function __construct(
        private XMLFileContentRetriever $xml_file_content_retriever,
        private \EventManager $event_manager,
        private ServiceEnableForXmlImportRetriever $event,
        private \PluginFactory $plugin_factory,
    ) {
    }

    /**
     * @param string[] $needed_extra_plugins
     */
    public function areAllServicesAvailable(string $file_path, array $needed_extra_plugins): bool
    {
        if (! is_file($file_path)) {
            throw new \RuntimeException('Invalid file path provided');
        }
        $xml = $this->xml_file_content_retriever->getSimpleXMLElementFromFilePath($file_path);

        $this->event->addServiceByName('file');
        $this->event->addServiceByName('summary');
        $this->event->addServiceByName('admin');
        $this->event_manager->processEvent($this->event);

        $available_services = $this->event->getAvailableServices();

        foreach ($xml->services->service as $service) {
            if (PHPCast::toBoolean($service['enabled']) === true) {
                $service_name = (string) $service['shortname'];
                if (! isset($available_services[$service_name])) {
                    return false;
                }
            }
        }

        foreach ($needed_extra_plugins as $plugin_name) {
            $plugin = $this->plugin_factory->getPluginByName($plugin_name);
            if ($plugin === false || ! $this->plugin_factory->isPluginAvailable($plugin)) {
                return false;
            }
        }

        return true;
    }
}
