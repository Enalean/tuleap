<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
 */

class Docman_SOAPActions extends Docman_Actions
{

    /**
     * Append a chunk of data to a file
     */
    public function appendFileChunk()
    {
        $request = $this->_controler->request;

        if ($request->exist('item_id')) {
            $item_id = $request->get('item_id');
            $item_factory = $this->_getItemFactory();
            $item = $item_factory->getItemFromDb($item_id);
            $itemType = $item_factory->getItemTypeForItem($item);

            if ($itemType == PLUGIN_DOCMAN_ITEM_TYPE_FILE) {
                $this->storeFileChunk($item);
            } else {
                $this->_controler->feedback->log('error', dgettext('tuleap-docman', 'The file cannot be found.'));
            }
        } else {
            $this->_controler->feedback->log('error', dgettext('tuleap-docman', 'Error while appending file chunk.'));
        }
    }

    /**
     * Adds a chunk to the last version of an existing file
     */
    private function storeFileChunk($item)
    {
        $fs       = $this->_getFileStorage();
        $request  = $this->_controler->request;
        if ($request->exist('chunk_offset') && $request->exist('chunk_size')) {
            $path = $fs->store($request->get('upload_content'), $request->get('group_id'), $item->getId(), $item->getCurrentVersion()->getNumber(), $request->get('chunk_offset'), $request->get('chunk_size'));
            if (!$path) {
                $this->_controler->feedback->log(
                    'error',
                    dgettext('tuleap-docman', 'Error while appending file chunk.')
                );
            }
        }
    }

    /**
     * Returns the MD5 checksum of a file (last version)
     */
    public function getFileMD5sum()
    {
        $request = $this->_controler->request;

        if ($request->exist('item_id')) {
            $item_id = $request->get('item_id');
            $item_factory = $this->_getItemFactory();
            $item = $item_factory->getItemFromDb($item_id);
            if ($item !== null) {
                $itemType = $item_factory->getItemTypeForItem($item);
                if ($itemType == PLUGIN_DOCMAN_ITEM_TYPE_FILE || $itemType == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) {
                    $fs = $this->_getFileStorage();

                    if ($request->existAndNonEmpty('all_versions')) {
                        $md5sum = array();
                        $vf = $this->_getVersionFactory();
                        $versions = $vf->getAllVersionForItem($item);
                        foreach ($versions as $version) {
                            $md5sum[$version->getNumber()] = $fs->getFileMD5sum($version->getPath());
                        }

                        // Sort by version order (ascending)
                        ksort($md5sum);

                        if (empty($md5sum)) {
                            $this->_controler->feedback->log('error', dgettext('tuleap-docman', 'Error while getting file checksum.'));
                        }
                    } else {
                        // if the version number is specified we compute the md5sum of this version else the last one
                        if ($request->existAndNonEmpty('version')) {
                            $vf = $this->_getVersionFactory();
                            $version = $vf->getSpecificVersion($item, $request->get('version'));
                            $md5sum = $fs->getFileMD5sum($version->getPath());
                        } else {
                            $md5sum = $fs->getFileMD5sum($item->getCurrentVersion()->getPath());
                        }
                        if (!$md5sum) {
                            $this->_controler->feedback->log('error', dgettext('tuleap-docman', 'Error while getting file checksum.'));
                        }
                    }

                    $this->_controler->_viewParams['action_result'] = $md5sum;
                } else {
                    $this->_controler->feedback->log('error', dgettext('tuleap-docman', 'The specified item is not a file.'));
                }
            } else {
                $this->_controler->feedback->log('error', dgettext('tuleap-docman', 'The file cannot be found.'));
            }
        } else {
            $this->_controler->feedback->log('error', dgettext('tuleap-docman', 'Error while getting file checksum.'));
        }
    }

    /**
     * Returns the (used) metadata of the given project
     */
    public function getProjectMetadata()
    {
        $request = $this->_controler->request;
        $groupId = $request->get('group_id');
        $metadataFactory = new Docman_MetadataFactory($groupId);
        $metadataList = array_merge($metadataFactory->getRealMetadataList(true), $metadataFactory->getHardCodedMetadataList(true));
        $this->_controler->_viewParams['action_result'] = $metadataList;
    }

    /**
     * Returns the list of values for the given list metadata.
     */
    public function getMetadataListOfValues()
    {
        $request = $this->_controler->request;
        $groupId = $request->get('group_id');
        $metadataFactory = new Docman_MetadataFactory($groupId);
        $metadataLovFactory = new Docman_MetadataListOfValuesElementFactory();

        $label = $request->get('label');

        $md = $metadataFactory->getFromLabel($label);

        $res = array();
        if ($md->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            foreach ($metadataLovFactory->getListByFieldId($md->id, $md->label, true) as $val) {
                $res[] = $val;
            }
        }

        $this->_controler->_viewParams['action_result'] = $res;
    }

    /**
     * Returns the list of items contained in the arborescence of the given folder
     * The items are summarized by the following attributes: id, parent_id, title, type, update_date, nb_versions
     */
    public function getTreeInfo()
    {
        $request = $this->_controler->request;
        $groupId = $request->get('group_id');

        $itemFactory = $this->_getItemFactory($groupId);

        $nb = 0;
        $params['user'] = $this->_controler->getUser();
        $params['getall'] = true;

        if ($request->exist('parent_id')) {
            $parent_id = $request->get('parent_id');
        }

        if (isset($parent_id) && $parent_id != 0) {
            $itemList = $itemFactory->getItemList($parent_id, $nb, $params);
            $itemList[] = $itemFactory->getItemFromDb($parent_id);

            $res = array();
            foreach ($itemList as $item) {
                $type = $itemFactory->getItemTypeForItem($item);
                if ($type == PLUGIN_DOCMAN_ITEM_TYPE_FILE || $type == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) {
                    $vf = $this->_getVersionFactory();
                    $nbVersions = count($vf->getAllVersionForItem($item));
                    if ($type == PLUGIN_DOCMAN_ITEM_TYPE_FILE) {
                        $filename = $item->getCurrentVersion()->getFilename();
                    } else {
                        $filename = $item->getTitle() . '.html';
                    }
                } else {
                    $nbVersions = null;
                    $filename   = null;
                }
                $res[] = array(
                             'id'          => $item->getId(),
                             'parent_id'   => $item->getParentId(),
                             'title'       => $item->getTitle(),
                             'filename'    => $filename,
                             'type'        => $type,
                             'nb_versions' => $nbVersions,
                         );
            }

            $this->_controler->_viewParams['action_result'] = $res;
        } else {
            $this->_controler->feedback->log('error', dgettext('tuleap-docman', 'Parameter parent_id is missing'));
        }
    }

    /**
     * Returns the content of an item (and if defined its version) base64 encoded.
     */
    public function getFileContents()
    {
        $request = $this->_controler->request;

        if ($request->exist('item_id')) {
            $item_id = $request->get('item_id');
            $item_factory = $this->_getItemFactory();
            $item = $item_factory->getItemFromDb($item_id);
            if ($item !== null) {
                $itemType = $item_factory->getItemTypeForItem($item);
                if ($itemType == PLUGIN_DOCMAN_ITEM_TYPE_FILE || $itemType == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) {
                    if ($request->exist('version_number')) {
                        $version_factory = $this->_getVersionFactory();
                        $version = $version_factory->getSpecificVersion($item, $request->get('version_number'));
                    } else {
                        $version = $item->getCurrentVersion();
                    }

                    if ($version) {
                        if (file_exists($version->getPath())) {
                            $this->_controler->_viewParams['action_result'] = base64_encode(file_get_contents($version->getPath()));
                        }
                    }
                } else {
                    $this->_controler->feedback->log('error', dgettext('tuleap-docman', 'The specified item is not a file.'));
                }
            } else {
                $this->_controler->feedback->log('error', dgettext('tuleap-docman', 'The file cannot be found.'));
            }
        } else {
            $this->_controler->feedback->log('error', dgettext('tuleap-docman', 'Error: item ID is missing.'));
        }
    }

    /**
     *  Returns a part (chunk) of the content, encoded in base64, of the file/embedded file which id
     *  item_id of a given version version_number, if the version is not specified it will be the current one, in the project group_id.
     */
    public function getFileChunk()
    {
        $request = $this->_controler->request;

        if ($request->exist('item_id')) {
            $item_id = $request->get('item_id');
            $item_factory = $this->_getItemFactory();
            $item = $item_factory->getItemFromDb($item_id);
            if ($item !== null) {
                $itemType = $item_factory->getItemTypeForItem($item);
                if ($itemType == PLUGIN_DOCMAN_ITEM_TYPE_FILE || $itemType == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) {
                    if ($request->exist('version_number')) {
                        $version_factory = $this->_getVersionFactory();
                        $version = $version_factory->getSpecificVersion($item, $request->get('version_number'));
                    } else {
                        $version = $item->getCurrentVersion();
                    }
                    if ($version) {
                        if (file_exists($version->getPath())) {
                            if ($request->exist('chunk_offset') && $request->exist('chunk_size')) {
                                $contents = file_get_contents($version->getPath(), null, null, $request->get('chunk_offset'), $request->get('chunk_size'));
                                $this->_controler->_viewParams['action_result'] = base64_encode($contents);
                            }
                        }
                    } else {
                        $this->_controler->feedback->log(
                            'error',
                            dgettext(
                                'tuleap-docman',
                                'Error: The given version of the item does not exist.'
                            )
                        );
                    }
                } else {
                    $this->_controler->feedback->log('error', dgettext('tuleap-docman', 'The specified item is not a file.'));
                }
            } else {
                $this->_controler->feedback->log('error', dgettext('tuleap-docman', 'The file cannot be found.'));
            }
        } else {
            $this->_controler->feedback->log('error', dgettext('tuleap-docman', 'Error: item ID is missing.'));
        }
    }
}
