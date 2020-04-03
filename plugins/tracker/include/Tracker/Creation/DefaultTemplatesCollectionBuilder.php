<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation;

use XML_Security;

class DefaultTemplatesCollectionBuilder
{
    /**
     * @var \EventManager
     */
    private $event_manager;

    public function __construct(\EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    public function build(): DefaultTemplatesCollection
    {
        $xml_files_collection = new DefaultTemplatesXMLFileCollection();
        $xml_files_collection->add(__DIR__ . '/../../../resources/templates/Tracker_Bugs.xml');
        $this->event_manager->processEvent($xml_files_collection);

        $collection = new DefaultTemplatesCollection();
        $xml_security = new XML_Security();
        foreach ($xml_files_collection->getXMLFiles() as $filepath) {
            $xml = $xml_security->loadFile($filepath);
            if (! (string) $xml->name) {
                continue;
            }
            if (! (string) $xml->item_name) {
                continue;
            }
            if (! (string) $xml->color) {
                continue;
            }
            if (! (string) $xml->description) {
                continue;
            }

            $id = 'default-' . (string) $xml->item_name;
            $collection->add(
                $id,
                new DefaultTemplate(
                    new TrackerTemplatesRepresentation($id, (string) $xml->name, (string) $xml->description, (string) $xml->color),
                    $filepath
                )
            );
        }

        return $collection;
    }
}
