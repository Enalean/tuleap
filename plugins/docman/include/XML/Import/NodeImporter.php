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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Docman\XML\Import;

use Docman_Item;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Docman\CannotInstantiateItemWeHaveJustCreatedInDBException;

class NodeImporter
{
    private const TYPE_FILE         = 'file';
    private const TYPE_EMBEDDEDFILE = 'embeddedfile';
    private const TYPE_WIKI         = 'wiki';
    private const TYPE_LINK         = 'link';
    private const TYPE_EMPTY        = 'empty';
    private const TYPE_FOLDER       = 'folder';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var PostFileImporter
     */
    private $file_importer;
    /**
     * @var ItemImporter
     */
    private $item_importer;
    /**
     * @var PostFolderImporter
     */
    private $folder_importer;
    /**
     * @var PostDoNothingImporter
     */
    private $do_nothing_importer;

    public function __construct(
        ItemImporter $item_importer,
        PostFileImporter $file_importer,
        PostFolderImporter $folder_importer,
        PostDoNothingImporter $do_nothing_importer,
        LoggerInterface $logger
    ) {
        $this->logger              = $logger;
        $this->item_importer       = $item_importer;
        $this->file_importer       = $file_importer;
        $this->folder_importer     = $folder_importer;
        $this->do_nothing_importer = $do_nothing_importer;
    }

    public function import(SimpleXMLElement $node, Docman_Item $parent_item, \PFUser $user): void
    {
        try {
            $this->importNode($node, $parent_item, $user);
        } catch (CannotInstantiateItemWeHaveJustCreatedInDBException $exception) {
            $this->logger->error('An error occurred while creating in DB the item: ' . $node->properties->title);
        } catch (UnknownItemTypeException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * @throws CannotInstantiateItemWeHaveJustCreatedInDBException|UnknownItemTypeException
     */
    private function importNode(SimpleXMLElement $node, Docman_Item $parent_item, \PFUser $user): void
    {
        $this->logger->debug("Importing {$node['type']}: " . $node->properties->title);

        $this->item_importer->import(
            $node,
            $this,
            $this->getPostImporter($node),
            $parent_item,
            $user,
            $this->getImportProperties($node)
        );
    }

    /**
     * @throws UnknownItemTypeException
     */
    private function getImportProperties(SimpleXMLElement $node): ImportProperties
    {
        $type        = (string) $node['type'];
        $title       = (string) $node->properties->title;
        $description = (string) $node->properties->description;

        switch ($type) {
            case self::TYPE_FILE:
                $properties = ImportProperties::buildFile($title, $description);
                break;

            case self::TYPE_EMBEDDEDFILE:
                $properties = ImportProperties::buildEmbedded($title, $description);
                break;

            case self::TYPE_WIKI:
                $properties = ImportProperties::buildWiki($title, $description, (string) $node->pagename);
                break;

            case self::TYPE_LINK:
                $properties = ImportProperties::buildLink($title, $description, (string) $node->url);
                break;

            case self::TYPE_EMPTY:
                $properties = ImportProperties::buildEmpty($title, $description);
                break;

            case self::TYPE_FOLDER:
                $properties = ImportProperties::buildFolder($title, $description);
                break;
            default:
                throw new UnknownItemTypeException($type);
        }

        return $properties;
    }

    private function getPostImporter(SimpleXMLElement $node)
    {
        $type = (string) $node['type'];
        if ($type === self::TYPE_FILE || $type === self::TYPE_EMBEDDEDFILE) {
            return $this->file_importer;
        }

        if ($type === self::TYPE_FOLDER) {
            return $this->folder_importer;
        }

        return $this->do_nothing_importer;
    }
}
