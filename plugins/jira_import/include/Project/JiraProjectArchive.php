<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\JiraImport\Project;

use Tuleap\Project\XML\Import\ArchiveInterface;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentDownloader;

class JiraProjectArchive implements ArchiveInterface
{
    /**
     * @var \SimpleXMLElement
     */
    private $project_xml;

    public function __construct(\SimpleXMLElement $project_xml)
    {
        $this->project_xml = $project_xml;
    }

    #[\Override]
    public function getProjectXML(): string
    {
        $xml = $this->project_xml->asXML();
        if ($xml === false) {
            return '';
        }
        return $xml;
    }

    #[\Override]
    public function getUsersXML(): string
    {
        return '';
    }

    #[\Override]
    public function extractFiles(): void
    {
    }

    #[\Override]
    public function getExtractionPath(): string
    {
        return AttachmentDownloader::getTmpFolderURL();
    }

    #[\Override]
    public function cleanUp(): void
    {
    }
}
