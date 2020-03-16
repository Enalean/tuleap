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

use Tuleap\Docman\Item\ItemVisitor;

/**
 * @template-implements ItemVisitor<DOMElement>
 */
class Docman_XMLExportVisitor implements ItemVisitor
{
    protected $doc;
    protected $statistics;
    protected $userCache;
    protected $dataPath;
    protected $logger;

    public function __construct(DOMDocument $doc, \Psr\Log\LoggerInterface $logger)
    {
        $this->doc = $doc;

        $this->fileCounter = 0;
        $this->userCache = array();
        $this->logger = $logger;

        $this->statistics['nb_items']   = 0;
        $this->statistics['nb_folder']  = 0;
        $this->statistics['nb_empty']   = 0;
        $this->statistics['nb_link']    = 0;
        $this->statistics['nb_wiki']    = 0;
        $this->statistics['nb_file']    = 0;
        $this->statistics['nb_version'] = 0;
        $this->statistics['nb_embedded'] = 0;
    }

    public function setDataPath($path)
    {
        $this->dataPath = $path;
    }

    public function visitItem(Docman_Item $item, array $params = [])
    {
        $type = str_replace('docman_', '', strtolower(get_class($item)));
        $node = $this->doc->createElement('item');

        $node->setAttribute('type', $type);

        $prop = $this->doc->createElement('properties');

        $this->appendChild($prop, 'title', $item->getTitle());
        $this->appendChild($prop, 'description', $item->getDescription());
        $this->appendChild($prop, 'create_date', date('c', $item->getCreateDate()));
        $this->appendChild($prop, 'update_date', date('c', $item->getUpdateDate()));
        $this->appendChild($prop, 'owner', $this->getNormalizedLogin($item->getOwnerId()));

        $prjSettings = Docman_SettingsBo::instance($item->getGroupId());
        if ($prjSettings->getMetadataUsage('status')) {
            $this->appendChild($prop, 'status', $this->getNormalizedStatus($item->getStatus()));
        }
        if ($prjSettings->getMetadataUsage('obsolescence_date') && $item->getObsolescenceDate() != 0) {
            $this->appendChild($prop, 'obsolescence_date', date('c', $item->getObsolescenceDate()));
        }

        $this->appendItemMetadataNode($prop, $item);

        $node->appendChild($prop);

        $this->statistics['nb_items']++;

        return $node;
    }

    protected function appendItemMetadataNode(DOMElement $node, Docman_Item $item)
    {
        $metaDataFactory = new Docman_MetadataFactory($item->getGroupId());
        $metaDataFactory->appendItemMetadataList($item);
        foreach ($item->getMetadata() as $metadata) {
            $real = $this->getNodeForMetadata($metadata);
            if ($real) {
                $node->appendChild($real);
            }
        }
    }

    protected function getNodeForMetadata(Docman_Metadata $metadata)
    {
        $metaDataFactory = new Docman_MetadataFactory($metadata->getGroupId());
        if ($metaDataFactory->isRealMetadata($metadata->getLabel())) {
            $node = $this->doc->createElement('property');
            $node->setAttribute('title', $metadata->getName());
            if ($metadata->getValue() instanceof ArrayIterator) {
                $this->getNodeForMetadataValues($metadata->getValue(), $node);
            } else {
                $value = $metadata->getValue();

                if ($value != '' && ($metadata->getType() == PLUGIN_DOCMAN_METADATA_TYPE_DATE)) {
                    $value = date('c', $value);
                }

                $node->appendChild($this->doc->createTextNode($value));
            }
            return $node;
        }
    }

    protected function getNodeForMetadataValues($mdValues, $mdNode)
    {
        foreach ($mdValues as $val) {
            if ($val->getId() != 100) {
                $node = $this->doc->createElement('value');
                $node->appendChild($this->doc->createTextNode($val->getName()));
                $mdNode->appendChild($node);
            }
        }
    }

    public function visitFolder(Docman_Folder $item, array $params = [])
    {
        $this->statistics['nb_folder']++;
        $n = $this->visitItem($item);
        $items = $item->getAllItems();
        if ($items->size()) {
            $it = $items->iterator();
            while ($it->valid()) {
                $o = $it->current();
                $n->appendChild($o->accept($this));
                $it->next();
            }
        }
        return $n;
    }

    public function visitDocument(Docman_Document $item, array $params = [])
    {
        return $this->visitItem($item);
    }

    public function visitWiki(Docman_Wiki $item, array $params = [])
    {
        $this->statistics['nb_wiki']++;
        $node = $this->visitDocument($item);
        $this->appendChild($node, 'pagename', $item->getPagename());
        return $node;
    }

    public function visitLink(Docman_Link $item, array $params = [])
    {
        $this->statistics['nb_link']++;
        $node = $this->visitDocument($item);
        $this->appendChild($node, 'url', $item->getUrl());
        return $node;
    }

    public function visitFile(Docman_File $item, array $params = [])
    {
        $this->statistics['nb_file']++;
        $n = $this->visitDocument($item);

        // Dump all versions
        $versionFactory = new Docman_VersionFactory();
        $versions = array_reverse($versionFactory->getAllVersionForItem($item));
        if (count($versions) > 0) {
            $vNode = $this->doc->createElement('versions');
            foreach ($versions as $version) {
                $this->statistics['nb_version']++;
                $vNode->appendChild($this->createVersion($version));
            }
            $n->appendChild($vNode);
        }
        return $n;
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = [])
    {
        $this->statistics['nb_embedded']++;
        return $this->visitFile($item);
    }

    public function visitEmpty(Docman_Empty $item, array $params = [])
    {
        $this->statistics['nb_empty']++;
        return $this->visitDocument($item);
    }

    protected function createVersion($version)
    {
        $vNode = $this->doc->createElement('version');
        $this->appendChild($vNode, 'author', $this->getNormalizedLogin($version->getAuthorId()));
        $this->appendChild($vNode, 'label', $version->getLabel());
        $this->appendChild($vNode, 'changelog', $version->getChangeLog());
        $this->appendChild($vNode, 'date', date('c', $version->getDate()));
        $this->appendChild($vNode, 'filename', $version->getFileName());
        $this->appendChild($vNode, 'filetype', $version->getFileType());
        $fileName = sprintf('content%05d.bin', $this->fileCounter++);
        $this->appendChild($vNode, 'content', $fileName);
        if (is_dir($this->dataPath)) {
            $res = copy($version->getPath(), $this->dataPath . '/' . $fileName);
            if (!$res) {
                echo $version->getPath() . " not copied to " . $this->dataPath . '/' . $fileName . "<br>";
                $this->logger->warning($version->getPath() . " not copied to [" . $this->dataPath . "]");
            } else {
                $this->logger->info($version->getPath() . " copied to [" . $this->dataPath . "]");
            }
        }
        return $vNode;
    }

    protected function getNormalizedLogin($userId)
    {
        if (!isset($this->userCache[$userId])) {
            $um = UserManager::instance();
            $user = $um->getUserById($userId);
            if ($user !== null) {
                $this->userCache[$userId] = $user->getName();
            } else {
                $this->userCache[$userId] = '';
            }
        }
        return $this->userCache[$userId];
    }

    protected function getNormalizedStatus($statusId)
    {
        switch ($statusId) {
            case PLUGIN_DOCMAN_ITEM_STATUS_NONE:
                return 'none';
            case PLUGIN_DOCMAN_ITEM_STATUS_DRAFT:
                return 'draft';
            case PLUGIN_DOCMAN_ITEM_STATUS_APPROVED:
                return 'approved';
            case PLUGIN_DOCMAN_ITEM_STATUS_REJECTED:
                return 'rejected';
        }
    }

    protected function setAttribute(DOMElement $node, $label, $value)
    {
        if ($value != '') {
            $node->setAttribute($label, $value);
        }
    }

    protected function appendChild(DOMElement $node, $label, $value)
    {
        if ($value != '') {
            $subNode = $this->doc->createElement($label);
            $subNode->appendChild($this->doc->createTextNode($value));
            $node->appendChild($subNode);
        }
    }

    public function getXML(Docman_Item $item)
    {
        return $item->accept($this);
    }

    public function displayStatistics()
    {
        var_dump($this->statistics);
    }
}
