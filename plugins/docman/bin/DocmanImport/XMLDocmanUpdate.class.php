<?php
/**
 * Originally written by Clément Plantier, 2008
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

require_once 'XMLDocmanImport.class.php';
require_once 'trees.php';

class XMLDocmanUpdate extends XMLDocmanImport {

    private $remoteItems = array();

    public function updatePath($xmlDoc, $parentId, $path) {
        $this->loadXML($xmlDoc);

        // Build the local item tree
        $localTree = $this->getTreeFromItemElement($this->findPath($path));

        // Build the remote item tree
        try {
            // If the parentId is not defined, take the root folder
            if ($parentId === null) {
                $parentId = $this->soap->getRootFolder($this->hash, $this->groupId);
            }
            
            $remoteItems = $this->soap->getDocmanTreeInfo($this->hash, $this->groupId, $parentId);
            foreach ($remoteItems as $item) {
                $this->remoteItems[$item->id] = $item;
            }

            $idtree = array_pop($this->buildDistantTreeFromSoapArray());
            $remoteTree = $this->getTitleTreeFromIdTree($idtree);
        } catch (SoapFault $e) {
            $this->printSoapResponseAndThrow($e);
        }

        // Merge the trees, and tag the nodes
        $mergedTree = array_pop(array_merge_tag_recursive($remoteTree, $localTree));
        
        $tagCounts = $this->tagCount($mergedTree);
        
        echo PHP_EOL."Number of items that will be updated: ".$tagCounts['IN_BOTH'].PHP_EOL;
        echo "Number of items that will be created: ".$tagCounts['IN_SECOND'].PHP_EOL;
        echo "Number of items that will be removed: ".$tagCounts['IN_FIRST'].PHP_EOL;
        
        echo "Are you sure you want to update the document tree? (y/n) [n] ";
        $answer = strtoupper(trim(fgets(STDIN)));
        
        if ($answer == 'Y') {
            foreach ($mergedTree['children'] as $childTitle => $subTree) {
                $this->recurseUpdateTree($childTitle, $subTree, $parentId);
            }
        }
    }
    
    /**
     * Count the occurences of the 3 different tags in the tree
     */
    private function tagCount($tree) {
        $counts = array('IN_BOTH' => 0, 'IN_FIRST' => 0, 'IN_SECOND' => 0);
        if (isset($tree['tag']) && isset($counts[$tree['tag']])) {
            $counts[$tree['tag']]++;
        }
        
        if (isset($tree['children'])) {
            foreach ($tree['children'] as $child) {
                $childCounts = $this->tagCount($child);
                foreach ($childCounts as $tag => $count) {
                    $counts[$tag] += $count;
                }
            }
        }
        return $counts;
    }

    /**
     * Recurse on the tree and do the right action for each node: create, update, or delete
     */
    private function recurseUpdateTree($title, $tree, $parentId) {
        if (isset($tree['id'])) {
            $itemId = $tree['id'];
        } else {
            $itemId = null;
        }
        
        if (isset($tree['tag'])) {
            switch ($tree['tag']) {
                case 'IN_FIRST':
                    // Only in server => delete item
                    $this->deleteItem($itemId, $title);
                    break;

                case 'IN_SECOND':
                    // Only in archive => create item
                    $this->recurseOnNode($tree['xmlElement'], $parentId);
                    break;

                case 'IN_BOTH':
                    // In both => update item
                    $this->updateItem($itemId, $tree['xmlElement']);

                    if (isset($tree['children'])) {
                        foreach ($tree['children'] as $childTitle => $subTree) {
                            $this->recurseUpdateTree($childTitle, $subTree, $itemId);
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Converts an array of items as returned by the SOAP function getDocmanTreeInfo to a tree of IDs
     */
    private function buildDistantTreeFromSoapArray() {

        $listOfNodes = array();
        foreach ($this->remoteItems as $id => $itemInfo) {
            $listOfNodes[$itemInfo->parent_id][] = $id;
        }

        // Tree of ids
        $tree = nodeListToTree($listOfNodes);

        return $tree;
    }

    /**
     * Returns a tree of titles based on a tree of IDs (recursive)
     */
    private function getTitleTreeFromIdTree(&$tree) {
        if ($tree == null) {
            return null;
        }

        foreach ($tree as $itemId => $itemChildren) {
            unset($tree[$itemId]);
            $title = $this->getItemTitle($itemId);
            
            if (isset($tree2[$title])) {
                $parentId = $this->remoteItems[$itemId]->parent_id;
                $parentTitle = $this->getItemTitle($parentId);
                if ($parentTitle == null) {
                    $parentTitle = "#$parentId";
                }
                
                $msg = "Several items have the title '$title' in the folder '$parentTitle' (server-side). In order to make use of the update function, please assure that all the items have distinct names in each folder.";
                $this->exitError($msg.PHP_EOL);
            }
            
            $tree2[$title]['id'] = $itemId;
            $children = $this->getTitleTreeFromIdTree($itemChildren);
            if ($children != null) {
                $tree2[$title]['children'] = $children;
            }
        }

        return $tree2;
    }

    /**
     * Gets the title of an item using its ID
     */
    private function getItemTitle($id) {
        if (isset($this->remoteItems[$id])) {
            return $this->remoteItems[$id]->title;
        } else {
            return "{root}";
        }
    }

    /**
     * Returns a tree from an XML element (recursive)
     */
    private function getTreeFromItemElement_rec($itemElement) {
        $tree['xmlElement'] = $itemElement;
        
        foreach ($itemElement->xpath('item') as $childItem) {
            $children = $this->getTreeFromItemElement_rec($childItem);
            $childTitle = (string)$childItem->properties->title;
            if (isset($tree['children'][$childTitle])) {
                $title = $itemElement->properties->title;
                $msg = "Several items have the title '$childTitle' in the folder '$title' (in the archive). In order to make use of the update function, please assure that all the items have distinct names in each folder.";
                $this->exitError($msg.PHP_EOL);
            }
            $tree['children'][$childTitle] = $children;
        }
        
        return $tree;
    }
    
    /**
     * Returns a tree from an XML element
     */
    private function getTreeFromItemElement($itemElement) {
        $title = (string)$itemElement->properties->title;
        return array($title => $this->getTreeFromItemElement_rec($itemElement));
    }

    /**
     * Deletes an item
     */
    private function deleteItem($itemId, $title) {
        do {
            $retry = false;

            echo "Deleting item              '$title'";

            try {
                $this->soap->deleteDocmanItem($this->hash, $this->groupId, $itemId);
                echo " #$itemId".PHP_EOL;
            } catch (Exception $e){
                $retry = parent::askWhatToDo($e);
            }
        } while ($retry);
    }

    /**
     * Updates an item
     */
    private function updateItem($itemId, $node) {

        $itemInfo = $this->getItemInformation($node);

        switch($node['type']) {
            case 'file':
                $this->updateFile($itemId, $itemInfo);
                
                $versions = $node->xpath('versions/version');
                $localVersionCount = count($versions);
                $remoteVersionCount = $this->remoteItems[$itemId]->nb_versions;
                
                if ($localVersionCount > $remoteVersionCount) {
                    // Send new versions
                    
                    $newVersionCount =  $localVersionCount - $remoteVersionCount;
                    $newVersions = array_slice($versions, $remoteVersionCount, $newVersionCount);
                    
                    foreach ($newVersions as $version) {
                        
                        list(
                            $file,
                            $label,
                            $changelog,
                            $author,
                            $date
                        ) = $this->getVersionInformation($version);
                        
                        $fileName = (string)$version->filename;
                        $fileType = (string)$version->filetype;
                        
                        $this->createFileVersion($itemId, $label, $changelog, $file, $fileName, $fileType, $author, $date);
                    }
                }
                break;

            case 'embeddedfile':
                $this->updateEmbeddedFile($itemId, $itemInfo);
                
                $versions = $node->xpath('versions/version');
                $localVersionCount = count($versions);
                $remoteVersionCount = $this->remoteItems[$itemId]->nb_versions;
                
                if ($localVersionCount > $remoteVersionCount) {
                    $newVersionCount =  $localVersionCount - $remoteVersionCount;
                    $newVersions = array_slice($versions, $remoteVersionCount, $newVersionCount);
                    
                    // Send the new versions
                    foreach ($newVersions as $version) {
                        
                        list(
                            $file,
                            $label,
                            $changelog,
                            $author,
                            $date
                        ) = $this->getVersionInformation($version);
                        
                        $this->createEmbeddedFileVersion($itemId, $label, $changelog, $file, $author, $date);
                    }
                }
                break;

            case 'wiki':
                $pageName = (string) $node->pagename;
                $this->updateWiki($itemId, $itemInfo, $pageName);
                break;

            case 'link':
                $url = (string) $node->url;
                $this->updateLink($itemId, $itemInfo, $url);
                break;

            case 'empty':
                $this->updateEmpty($itemId, $itemInfo);
                break;

            case 'folder':
                $this->updateFolder($itemId, $itemInfo);
                break;
        }
    }
    
    /**
     * Updates a file
     */
    private function updateFile($itemId, $itemInfo) {
        // Assign variables
        list(
            $title,
            $description,
            $status,
            $obsolescenceDate,
            $owner,
            $createDate,
            $updateDate,
            $metadata,
            $permissions
        ) = $itemInfo;

        do {
            $retry = false;

            echo "Updating file              '$title'";

            try {
                $this->soap->updateDocmanFile($this->hash, $this->groupId, $itemId, $title, $description, $status, $obsolescenceDate, $permissions, $metadata, $owner, $createDate, $updateDate);
                echo " #$itemId".PHP_EOL;
            } catch (Exception $e){
                $retry = self::askWhatToDo($e);
            }
        } while ($retry);
    }
    
    /**
     * Updates an embedded file
     */
    private function updateEmbeddedFile($itemId, $itemInfo) {
        // Assign variables
        list(
            $title,
            $description,
            $status,
            $obsolescenceDate,
            $owner,
            $createDate,
            $updateDate,
            $metadata,
            $permissions
        ) = $itemInfo;

        do {
            $retry = false;

            echo "Updating embedded file     '$title'";

            try {
                $this->soap->updateDocmanEmbeddedFile($this->hash, $this->groupId, $itemId, $title, $description, $status, $obsolescenceDate, $permissions, $metadata, $owner, $createDate, $updateDate);
                echo " #$itemId".PHP_EOL;
            } catch (Exception $e){
                $retry = self::askWhatToDo($e);
            }
        } while ($retry);
    }

    /**
     * Updates an empty document
     */
    private function updateEmpty($itemId, $itemInfo) {
        // Assign variables
        list(
            $title,
            $description,
            $status,
            $obsolescenceDate,
            $owner,
            $createDate,
            $updateDate,
            $metadata,
            $permissions
        ) = $itemInfo;

        do {
            $retry = false;

            echo "Updating empty document    '$title'";

            try {
                $this->soap->updateDocmanEmptyDocument($this->hash, $this->groupId, $itemId, $title, $description, $status, $obsolescenceDate, $permissions, $metadata, $owner, $createDate, $updateDate);
                echo " #$itemId".PHP_EOL;
            } catch (Exception $e){
                $retry = self::askWhatToDo($e);
            }
        } while ($retry);
    }
    
    /**
     * Updates a wiki document
     */
    private function updateWiki($itemId, $itemInfo, $pageName) {
        // Assign variables
        list(
            $title,
            $description,
            $status,
            $obsolescenceDate,
            $owner,
            $createDate,
            $updateDate,
            $metadata,
            $permissions
        ) = $itemInfo;

        do {
            $retry = false;

            echo "Updating wiki page         '$title' ($pageName)";

            try {
                $this->soap->updateDocmanWikiPage($this->hash, $this->groupId, $itemId, $title, $description, $status, $obsolescenceDate, $pageName, $permissions, $metadata, $owner, $createDate, $updateDate);
                echo " #$itemId".PHP_EOL;
            } catch (Exception $e){
                $retry = self::askWhatToDo($e);
            }
        } while ($retry);
    }
    
    /**
     * Updates a link
     */
    private function updateLink($itemId, $itemInfo, $url) {
        // Assign variables
        list(
            $title,
            $description,
            $status,
            $obsolescenceDate,
            $owner,
            $createDate,
            $updateDate,
            $metadata,
            $permissions
        ) = $itemInfo;

        do {
            $retry = false;

            echo "Updating link              '$title' ($url)";

            try {
                $this->soap->updateDocmanLink($this->hash, $this->groupId, $itemId, $title, $description, $status, $obsolescenceDate, $url, $permissions, $metadata, $owner, $createDate, $updateDate);
                echo " #$itemId".PHP_EOL;
            } catch (Exception $e){
                $retry = self::askWhatToDo($e);
            }
        } while ($retry);
    }
    
    /**
     * Updates a folder
     */
    private function updateFolder($itemId, $itemInfo) {
        // Assign variables
        list(
            $title,
            $description,
            $status,
            $obsolescenceDate,
            $owner,
            $createDate,
            $updateDate,
            $metadata,
            $permissions
        ) = $itemInfo;

        do {
            $retry = false;

            echo "Updating folder            '$title'";

            try {
                $this->soap->updateDocmanFolder($this->hash, $this->groupId, $itemId, $title, $description, $status, $permissions, $metadata, $owner, $createDate, $updateDate);
                echo " #$itemId".PHP_EOL;
            } catch (Exception $e){
                $retry = self::askWhatToDo($e);
            }
        } while ($retry);
    }
}

?>