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
 */

namespace Tuleap\XML;

class ProjectXMLMerger
{

    public function merge($source1_filename, $source2_filename, $destination_filename)
    {
        $source1 = simplexml_load_string(file_get_contents($source1_filename));
        $source2 = simplexml_load_string(file_get_contents($source2_filename));

        $dom_project = dom_import_simplexml($source1);

        foreach ($source2->children() as $child) {
            $dom_service = dom_import_simplexml($child);
            if ($dom_project->ownerDocument === null) {
                continue;
            }
            $dom_service = $dom_project->ownerDocument->importNode($dom_service, true);
            $dom_project->appendChild($dom_service);
        }

        $source1->asXML($destination_filename);
    }
}
