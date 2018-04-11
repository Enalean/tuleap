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

use EventManager;
use ForgeConfig;
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
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(TrackerXmlExport $exporter, XMLConvertor $convertor, EventManager $event_manager)
    {
        $this->exporter      = $exporter;
        $this->convertor     = $convertor;
        $this->event_manager = $event_manager;
    }

    public function exportArtifactAndTrackerStructureToXML(PFUser $user, \Tracker_Artifact $artifact)
    {
        $archive_path = ForgeConfig::get('tmp_dir') . '/artifact_' . $artifact->getId();
        $archive      = new ZipArchive($archive_path);

        $xml_element = $this->exporter->exportSingleTrackerBunchOfArtifactsToXml(
            $artifact->getTracker()->getId(),
            $user,
            $archive,
            [$artifact]
        );

        $archive->addFromString("artifact.xml", $this->convertor->convertToXml($xml_element));
        $archive->close();

        $status = true;
        $error  = null;
        $params = array(
            'source_path'     => $archive->getArchivePath(),
            'archive_prefix'  => 'deleted_',
            'status'          => &$status,
            'error'           => &$error,
            'skip_duplicated' => false
        );

        $this->event_manager->processEvent('archive_deleted_item', $params);

        unlink($archive->getArchivePath());
    }
}
