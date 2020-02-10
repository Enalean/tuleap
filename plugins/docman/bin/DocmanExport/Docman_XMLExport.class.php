<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once(__DIR__ . '/../../include/Docman_ItemFactory.class.php');
require 'Docman_XMLExportVisitor.class.php';

class Docman_XMLExport
{
    protected $groupId;
    protected $dataPath;
    protected $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }

    public function setDataPath($path)
    {
        $this->dataPath = $path;
    }

    public function getXML($doc)
    {
        $docman = $doc->createElement('docman');
        $docman->appendChild($this->getMetadataDef($doc));
        //$docman->appendChild($this->getGroupsDef($doc));
        $docman->appendChild($this->getTree($doc));
        return $docman;
    }

    /**
     * Should be transfered in Docman_Metadata
     * @return DOMNode
     */
    public function getMetadataDef(DOMDocument $doc)
    {
        $propdefs = $doc->createElement('propdefs');
        $mdFactory = new Docman_MetadataFactory($this->groupId);
        foreach ($mdFactory->getRealMetadataList() as $metadata) {
            $propdef = $doc->createElement('propdef');
            $propdef->setAttribute('name', $metadata->getName());
            switch ($metadata->getType()) {
                case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
                    $type = 'text';
                    break;
                case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
                    $type = 'string';
                    break;
                case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                    $type = 'date';
                    break;
                case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                    $type = 'list';
                    break;
            }
            $propdef->setAttribute('type', $type);
            $propdef->setAttribute('multivalue', $metadata->getIsMultipleValuesAllowed() ? 'true' : 'false');
            $propdef->setAttribute('empty', $metadata->getIsEmptyAllowed() ? 'true' : 'false');
            if ($metadata->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                $loveFactory = new Docman_MetadataListOfValuesElementFactory($metadata->getId());
                foreach ($loveFactory->getListByFieldId($metadata->getId(), $metadata->getLabel(), true) as $love) {
                    if ($love->getId() != 100) {
                        $value = $doc->createElement('value');
                        $value->appendChild($doc->createTextNode($love->getName()));
                        $propdef->appendChild($value);
                    }
                }
            }
            $propdefs->appendChild($propdef);
        }
        return $propdefs;
    }

    public function getTree(DOMDocument $doc)
    {
        // Get root item
        $itemFactory = new Docman_ItemFactory($this->groupId);
        $user = UserManager::instance()->getCurrentUser();

        $rootItem = $itemFactory->getRoot($this->groupId);
        $tree = $itemFactory->getItemSubTree($rootItem, $user, true, true);

        $xmlExport = new Docman_XMLExportVisitor($doc, $this->logger);
        $xmlExport->setDataPath($this->dataPath);

        return $xmlExport->getXML($tree);
    }
}
