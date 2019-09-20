<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Tuleap\Project\ProjectAccessChecker;

/**
 * This class lists the services of a given project that can be accessed using WebDAV
 *
 * It is an implementation of the abstract class Sabre_DAV_Directory methods
 *
 */
class WebDAVProject extends Sabre_DAV_Directory
{
    private $user;
    private $project;
    private $maxFileSize;
    /**
     * @var ProjectAccessChecker
     */
    private $access_checker;

    /**
     * Constuctor of the class
     *
     * @param PFUser $user
     * @param Project $project
     * @param int $maxFileSize
     *
     * @return void
     */
    public function __construct(PFUser $user, Project $project, $maxFileSize, ProjectAccessChecker $access_checker)
    {
        $this->user = $user;
        $this->project = $project;
        $this->maxFileSize = $maxFileSize;
        $this->access_checker = $access_checker;
    }

    /**
     * Generates the list of services under the project
     *
     * @return array
     */
    function getChildren()
    {
        $children = array();
        if ($this->usesFile()) {
            $children[$GLOBALS['Language']->getText('plugin_webdav_common', 'files')] = $this->getWebDAFRS();
        }

        $em    = $this->getUtils()->getEventManager();
        $roots = array();
        $em->processEvent('webdav_root_for_service', array('project' => $this->getProject(),
                                                           'roots'    => &$roots));
        foreach ($roots as $service => $root) {
            if ($service == 'docman') {
                $docman = new WebDAVDocmanFolder($this->getUser(), $this->getProject(), $root);
                $children[$docman->getName()] = $docman;
            }
        }
        return $children;
    }

    /**
     * Returns the given service
     *
     * @param String $service
     *
     * @return WebDAVFRS
     *
     * @see lib/Sabre/DAV/Sabre_DAV_Directory#getChild($name)
     */
    function getChild($service)
    {
        $children = $this->getChildren();
        if (isset($children[$service])) {
            return $children[$service];
        } else {
            throw new Sabre_DAV_Exception_FileNotFound($GLOBALS['Language']->getText('plugin_webdav_common', 'service_not_available'));
        }
    }

    /**
     * Returns the name of the project
     *
     * @return String
     *
     * @see lib/Sabre/DAV/Sabre_DAV_INode#getName()
     */
    function getName()
    {

        $utils = $this->getUtils();
        return $utils->unconvertHTMLSpecialChars($this->getProject()->getUnixName());
    }

    /**
     * Projects don't have a last modified date this
     * is used only to suit the class Sabre_DAV_Node
     *
     * @return NULL
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Node#getLastModified()
     */
    function getLastModified()
    {

        return;
    }

    /**
     * Returns the project
     *
     * @return Project
     */
    function getProject()
    {

        return $this->project;
    }

    /**
     * Returns the project Id
     *
     * @return int
     */
    function getGroupId()
    {

        return $this->getProject()->getGroupId();
    }

    /**
     * Returns the user
     *
     * @return PFUser
     */
    function getUser()
    {

        return $this->user;
    }

    /**
     * Returns an instance of WebDAVUtils
     *
     * @return WebDAVUtils
     */
    function getUtils()
    {

        return WebDAVUtils::getInstance();
    }

    /**
     * Returns the max file size
     *
     * @return int
     */
    function getMaxFileSize()
    {
        return $this->maxFileSize;
    }

    /**
     * Returns whether the project exist or not
     *
     * @return bool
     */
    function exist()
    {

        // D refers to deleted
        return !$this->getProject()->isError() && $this->getProject()->getStatus() != 'D';
    }

    /**
     * Returns whether the project is active or not
     *
     * @return bool
     */
    function isActive()
    {

        return $this->getProject()->isActive();
    }

    /**
     * Return a new WebDAVFRS
     *
     * @return WebDAVFRS
     */
    function getWebDAFRS()
    {
        return new WebDAVFRS($this->getUser(), $this->getProject(), $this->getMaxFileSize());
    }

    /**
     * Returns whether the project uses files or not
     *
     * @return bool
     */
    function usesFile()
    {

        return $this->getProject()->usesFile();
    }

    /**
     * Checks whether the user can read the project or not
     *
     * @return bool
     */
    public function userCanRead()
    {
        try {
            $this->access_checker->checkUserCanAccessProject($this->getUser(), $this->getProject());
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
