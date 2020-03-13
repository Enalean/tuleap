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
    public function getChildren()
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
    public function getChild($service)
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
    public function getName()
    {
        $utils = $this->getUtils();
        return $this->getProject()->getUnixName();
    }

    /**
     * Projects don't have a last modified date this
     * is used only to suit the class Sabre_DAV_Node
     *
     * @see plugins/webdav/lib/Sabre/DAV/Sabre_DAV_Node#getLastModified()
     */
    public function getLastModified()
    {
        return 0;
    }

    /**
     * Returns the project
     *
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Returns the project Id
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->getProject()->getGroupId();
    }

    /**
     * Returns the user
     *
     * @return PFUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Returns an instance of WebDAVUtils
     *
     * @return WebDAVUtils
     */
    public function getUtils()
    {
        return WebDAVUtils::getInstance();
    }

    /**
     * Returns the max file size
     *
     * @return int
     */
    public function getMaxFileSize()
    {
        return $this->maxFileSize;
    }

    /**
     * Returns whether the project exist or not
     *
     * @return bool
     */
    public function exist()
    {
        // D refers to deleted
        return !$this->getProject()->isError() && $this->getProject()->getStatus() != 'D';
    }

    /**
     * Returns whether the project is active or not
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->getProject()->isActive();
    }

    /**
     * Return a new WebDAVFRS
     *
     * @return WebDAVFRS
     */
    public function getWebDAFRS()
    {
        return new WebDAVFRS($this->getUser(), $this->getProject(), $this->getMaxFileSize());
    }

    /**
     * Returns whether the project uses files or not
     *
     * @return bool
     */
    public function usesFile()
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
