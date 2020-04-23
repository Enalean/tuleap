<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\XML\Importer;

use ForgeConfig;
use Project;
use SimpleXMLElement;

class TrackerXmlSaver
{
    public function storeUsedXmlForTrackersCreation(Project $project, SimpleXMLElement $xml): void
    {
        $file_system_folder  = ForgeConfig::get('sys_data_dir') . '/xml_import/';

        if (! is_dir($file_system_folder)) {
            if (! mkdir($file_system_folder, 750) && ! is_dir($file_system_folder)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $file_system_folder));
            }
        }

        $import_date = new \DateTimeImmutable();
        $path = $file_system_folder . $project->getId() . '_tracker_import_' . $import_date->getTimestamp() . '.xml';
        $xml->asXML($path);
    }
}
