<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Docman\XML\Export;

use Docman_Document;
use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Version;
use Docman_VersionFactory;
use Docman_Wiki;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Docman\Item\ItemVisitor;
use Tuleap\Project\XML\Export\ArchiveInterface;
use Tuleap\xml\XMLDateHelper;
use UserXMLExporter;
use XML_SimpleXMLCDATAFactory;

/**
 * @template-implements ItemVisitor<SimpleXMLElement>
 */
class XMLExportVisitor implements ItemVisitor
{
    /**
     * @var ArchiveInterface
     */
    private $archive;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Docman_VersionFactory
     */
    private $version_factory;
    /**
     * @var UserXMLExporter
     */
    private $user_exporter;
    /**
     * @var PermissionsExporter
     */
    private $permissions_exporter;

    public function __construct(
        LoggerInterface $logger,
        ArchiveInterface $archive,
        Docman_VersionFactory $version_factory,
        UserXMLExporter $user_exporter,
        PermissionsExporter $permissions_exporter
    ) {
        $this->logger               = $logger;
        $this->archive              = $archive;
        $this->version_factory      = $version_factory;
        $this->user_exporter        = $user_exporter;
        $this->permissions_exporter = $permissions_exporter;
    }

    public function export(SimpleXMLElement $xml, Docman_Item $item): void
    {
        $item->accept($this, ['xml' => $xml]);
    }

    public function visitItem(Docman_Item $item, array $params = []): SimpleXMLElement
    {
        $type = str_replace('docman_', '', strtolower(get_class($item)));
        $this->log($item, $type);

        $xml = $params['xml'];
        assert($xml instanceof SimpleXMLElement);

        $node = $xml->addChild('item');
        $node->addAttribute('type', $type);

        $this->exportProperties($node, $item);
        $this->permissions_exporter->exportPermissions($node, $item);

        return $node;
    }

    public function visitFolder(Docman_Folder $item, array $params = []): SimpleXMLElement
    {
        $node  = $this->visitItem($item, $params);
        $items = $item->getAllItems();
        foreach ($items->iterator() as $child) {
            $child->accept($this, ['xml' => $node]);
        }

        return $node;
    }

    public function visitDocument(Docman_Document $item, array $params = []): SimpleXMLElement
    {
        return $this->visitItem($item, $params);
    }

    public function visitWiki(Docman_Wiki $item, array $params = []): SimpleXMLElement
    {
        $this->logger->warning(
            sprintf(
                "Cannot export wiki item #%d (%s). Export/import of wiki documents is not supported.",
                $item->getId(),
                $item->getTitle()
            )
        );

        $xml = $params['xml'];
        assert($xml instanceof SimpleXMLElement);

        return $xml;
    }

    public function visitLink(Docman_Link $item, array $params = []): SimpleXMLElement
    {
        $node = $this->visitDocument($item, $params);
        $this->appendTextChild($node, 'url', $item->getUrl());

        return $node;
    }

    public function visitFile(Docman_File $item, array $params = []): SimpleXMLElement
    {
        $node = $this->visitDocument($item, $params);

        $versions = array_reverse($this->version_factory->getAllVersionForItem($item));
        if (count($versions) > 0) {
            $node_for_versions = $node->addChild('versions');
            foreach ($versions as $version) {
                $this->createVersion($node_for_versions, $version);
            }
        }

        return $node;
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []): SimpleXMLElement
    {
        return $this->visitFile($item, $params);
    }

    public function visitEmpty(Docman_Empty $item, array $params = []): SimpleXMLElement
    {
        return $this->visitDocument($item, $params);
    }

    private function createVersion(SimpleXMLElement $xml, Docman_Version $version): void
    {
        $node = $xml->addChild('version');
        $this->appendTextChild($node, 'filename', $version->getFileName());
        $this->appendTextChild($node, 'filetype', $version->getFileType());
        $this->appendTextChild($node, 'filesize', $version->getFilesize());

        $date = $version->getDate();
        if ($date) {
            XMLDateHelper::addChild($node, 'date', (new \DateTimeImmutable())->setTimestamp($date));
        }

        $user_id = $version->getAuthorId();
        if ($user_id) {
            $this->user_exporter->exportUserByUserId($user_id, $node, 'author');
        }

        $label = $version->getLabel();
        if ($label) {
            $this->appendTextChild($node, 'label', $label);
        }

        $changelog = $version->getChangelog();
        if ($changelog) {
            $this->appendTextChild($node, 'changelog', $changelog);
        }

        $file_name = 'documents/' . sprintf('content-%d.bin', $version->getId());
        $this->appendTextChild($node, 'content', $file_name);
        $this->archive->addFile($file_name, $version->getPath());
    }

    private function appendTextChild(SimpleXMLElement $node, $label, $value): void
    {
        if (empty($value)) {
            return;
        }
        $cdata_factory = new XML_SimpleXMLCDATAFactory();
        $cdata_factory->insert($node, $label, $value);
    }

    private function log(Docman_Item $item, string $type): void
    {
        $this->logger->debug(
            sprintf(
                "Exporting %s item #%d: %s",
                $type,
                $item->getId(),
                $item->getTitle()
            )
        );
    }

    private function exportProperties(SimpleXMLElement $node, Docman_Item $item): void
    {
        $properties = $node->addChild('properties');
        $this->appendTextChild($properties, 'title', $item->getTitle());

        $description = $item->getDescription();
        if ($description) {
            $this->appendTextChild($properties, 'description', $description);
        }

        $create_date = $item->getCreateDate();
        $date_time   = new \DateTimeImmutable();
        if ($create_date) {
            XMLDateHelper::addChild($properties, 'create_date', $date_time->setTimestamp($create_date));
        }

        $update_date = $item->getUpdateDate();
        if ($update_date) {
            XMLDateHelper::addChild($properties, 'update_date', ($date_time)->setTimestamp($update_date));
        }

        $user_id = $item->getOwnerId();
        if ($user_id) {
            $this->user_exporter->exportUserByUserId($user_id, $properties, 'owner');
        }
    }
}
