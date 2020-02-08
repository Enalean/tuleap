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

namespace Tuleap\Tracker\Artifact\ArtifactsDeletion;

use ForgeConfig;
use PFUser;
use Tracker_Artifact;
use Tuleap\Event\Events\ArchiveDeletedItemProvider;
use Tuleap\Tracker\Artifact\ArtifactWithTrackerStructureExporter;
use Tuleap\Project\XML\Export\ZipArchive;

class ArchiveDeletedArtifactProvider implements ArchiveDeletedItemProvider
{
    /**
     * @var ArtifactWithTrackerStructureExporter
     */
    private $artifact_with_tracker_structure_exporter;
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    /**
     * @var PFUser
     */
    private $user;

    private $archive_path;

    public function __construct(ArtifactWithTrackerStructureExporter $artifact_with_tracker_structure_exporter, Tracker_Artifact $artifact, PFUser $user)
    {
        $this->artifact_with_tracker_structure_exporter = $artifact_with_tracker_structure_exporter;
        $this->artifact = $artifact;
        $this->user = $user;
    }

    /**
     * Generates the archive only when archive path is requested
     *
     * This specific implementation was made to avoid generating a fat archive that takes ages to generate
     * while the archivedeleteditems plugin is not even installed on the server.
     *
     * @throws \Tuleap\Project\XML\ArchiveException
     */
    public function getArchivePath(): string
    {
        $this->archive_path = ForgeConfig::get('tmp_dir') . '/artifact_' . $this->artifact->getId() . '_' . time() . '.zip';
        $archive      = new ZipArchive($this->archive_path);
        $this->artifact_with_tracker_structure_exporter->exportArtifactAndTrackerStructureToXML($this->user, $this->artifact, $archive);
        $archive->close();
        return $this->archive_path;
    }

    public function getPrefix(): string
    {
        return 'deleted_';
    }

    public function purge(): void
    {
        if ($this->archive_path && file_exists($this->archive_path)) {
            unlink($this->archive_path);
        }
    }
}
