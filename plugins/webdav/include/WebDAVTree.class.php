<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

/**
 * This is the WebDAV server tree it implements Sabre_DAV_ObjectTree to rewrite some methods
 */
class WebDAVTree extends \Sabre\DAV\Tree
{

    /**
     * Tests if the release destination is a package
     * we allow moving releases only within the same project
     *
     * @param WebDAVFRSRelease $release
     * @param mixed $destination
     *
     * @return boolean
     */
    function releaseCanBeMoved($release, $destination) {
        return (($destination instanceof WebDAVFRSPackage)
        && ($release->getProject()->getGroupId() == $destination->getProject()->getGroupId()));
    }

    /**
     * Tests if the file destination is a release
     * we allow moving files only within the same project
     *
     * @param WebDAVFRSFile $file
     * @param mixed $destination
     *
     * @return boolean
     */
    function fileCanBeMoved($file, $destination) {
        return (($destination instanceof WebDAVFRSRelease)
        && ($file->getProject()->getGroupId() == $destination->getProject()->getGroupId()));
    }

    /**
     * Tests if the node can be moved or not
     *
     * @param mixed $source
     * @param mixed $destination
     *
     * @return boolean
     */
    function canBeMoved($source, $destination) {
        return(($source instanceof WebDAVFRSRelease && $this->releaseCanBeMoved($source, $destination))
        || ($source instanceof WebDAVFRSFile && $this->fileCanBeMoved($source, $destination)));
    }

    /**
     * Copy a docman item
     * We don't allow copying docman items from a project to another
     * We don't allow copying FRS items
     *
     * Copy or move of items is disabled as of today, because we need more feedback on
     * how basic (create/update/delete) features works before allowing it.
     *
     * @param String $sourcePath
     * @param String $destinationPath
     *
     * @return void
     */
    public function copy($sourcePath, $destinationPath)
    {
        throw new \Sabre\DAV\Exception\MethodNotAllowed(
            $GLOBALS['Language']->getText('plugin_webdav_common', 'write_access_disabled')
        );
    }

    /**
     * This method moves nodes from location to another
     *
     * Move only allowed to rename a file in a given release. Otherwise as this
     * operation is not yet well supported by the FRS itself we cannot implement
     * it the right way.
     *
     * @return void
     */
    public function move($sourcePath, $destinationPath) {
        list($sourceDir, $sourceName) = \Sabre\HTTP\URLUtil::splitPath($sourcePath);
        list($destinationDir, $destinationName) = \Sabre\HTTP\URLUtil::splitPath($destinationPath);

        $source = $this->getNodeForPath($sourcePath);
        $itemFactory = $this->getUtils()->getDocmanItemFactory();
        $destination = $this->getNodeForPath($destinationDir);
        // Check that write access is enabled for WebDAV
        if ($this->getUtils()->isWriteEnabled()) {
            if ($sourceDir === $destinationDir) {
                $source->setName($destinationName);
            } else {
                throw new \Sabre\DAV\Exception\MethodNotAllowed(
                    $GLOBALS['Language']->getText('plugin_webdav_common', 'move_error')
                );
            }
        } else {
            throw new \Sabre\DAV\Exception\MethodNotAllowed(
                $GLOBALS['Language']->getText('plugin_webdav_common', 'write_access_disabled')
            );
        }
    }

    /**
     * Returns an instance of WebDAVUtils
     *
     * @return WebDAVUtils
     */
    function getUtils() {
        return WebDAVUtils::getInstance();
    }
}
