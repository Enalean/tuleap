<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Artifact;

use PFUser;
use TrackerXmlExport;
use Tuleap\Project\XML\Export\ZipArchive;
use Tuleap\XMLConvertor;

class ArtifactWithTrackerStructureExporter
{
    /**
     * @var TrackerXmlExport
     */
    private $exporter;
    /**
     * @var XMLConvertor
     */
    private $convertor;

    public function __construct(TrackerXmlExport $exporter, XMLConvertor $convertor)
    {
        $this->exporter      = $exporter;
        $this->convertor     = $convertor;
    }

    public function exportArtifactAndTrackerStructureToXML(PFUser $user, \Tuleap\Tracker\Artifact\Artifact $artifact, ZipArchive $archive)
    {
        $xml_element = $this->exporter->exportSingleTrackerBunchOfArtifactsToXml(
            $artifact->getTracker()->getId(),
            $user,
            $archive,
            [$artifact]
        );

        $archive->addFromString("artifact.xml", $this->convertor->convertToXml($xml_element));
    }
}
