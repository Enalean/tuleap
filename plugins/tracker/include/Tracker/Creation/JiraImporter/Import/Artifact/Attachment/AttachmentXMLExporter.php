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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment;

use SimpleXMLElement;
use XML_SimpleXMLCDATAFactory;

class AttachmentXMLExporter
{
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $cdata_factory;

    /**
     * @var AttachmentDownloader
     */
    private $attachment_downloader;

    public function __construct(
        AttachmentDownloader $attachment_downloader,
        XML_SimpleXMLCDATAFactory $cdata_factory
    ) {
        $this->cdata_factory         = $cdata_factory;
        $this->attachment_downloader = $attachment_downloader;
    }

    public function exportCollectionOfAttachmentInXML(
        AttachmentCollection $attachment_collection,
        SimpleXMLElement $artifact_node
    ): void {
        foreach ($attachment_collection->getAttachments() as $attachment) {
            $downloaded_file_name = $this->attachment_downloader->downloadAttachment($attachment);

            $file_node = $artifact_node->addChild('file');
            $file_node->addAttribute('id', 'fileinfo_' . (string) $attachment->getId());

            $this->cdata_factory->insert(
                $file_node,
                'filename',
                $attachment->getFilename()
            );

            $this->cdata_factory->insert(
                $file_node,
                'path',
                $downloaded_file_name
            );

            $file_node->addChild('filesize', (string) $attachment->getSize());
            $file_node->addChild('filetype', (string) $attachment->getMimeType());
            $file_node->addChild('description', '');
        }
    }
}
