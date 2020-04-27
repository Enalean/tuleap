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
use SimpleXMLElement;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;

class TrackerXmlSaver
{
    public function storeUsedXmlForTrackersCreation(TrackerXmlImportConfig $import_config, SimpleXMLElement $xml): void
    {
        $file_system_folder = $import_config->getFileSystemFolder();
        if (! is_dir($file_system_folder)) {
            if (! mkdir($file_system_folder, 750) && ! is_dir($file_system_folder)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $file_system_folder));
            }

            chown($file_system_folder, ForgeConfig::get('sys_http_user'));
            chgrp($file_system_folder, ForgeConfig::get('sys_http_user'));
        }

        $xml->asXML($import_config->getPathToXml());
    }
}
