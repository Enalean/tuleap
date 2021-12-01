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

/**
 * This class lists the services of a given project that can be accessed using WebDAV
 */
class WebDAVProject extends \Sabre\DAV\FS\Directory
{
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var int
     */
    private $maxFileSize;
    /**
     * @var WebDAVUtils
     */
    private $utils;

    public function __construct(
        PFUser $user,
        Project $project,
        int $maxFileSize,
        WebDAVUtils $utils,
    ) {
        $this->user        = $user;
        $this->project     = $project;
        $this->maxFileSize = $maxFileSize;
        $this->utils       = $utils;
    }

    /**
     * Generates the list of services under the project
     *
     * @return \Sabre\DAV\INode[]
     */
    public function getChildren(): array
    {
        $children = [];
        if ($this->project->usesFile()) {
            $children[$GLOBALS['Language']->getText('plugin_webdav_common', 'files')] = new WebDAVFRS($this->user, $this->project, $this->maxFileSize);
        }

        $em    = $this->utils->getEventManager();
        $roots = [];
        $em->processEvent('webdav_root_for_service', ['project' => $this->project,
                                                           'roots'    => &$roots]);
        foreach ($roots as $service => $root) {
            if ($service === 'docman') {
                $docman                       = new WebDAVDocmanFolder($this->user, $this->project, $root, $this->utils);
                $children[$docman->getName()] = $docman;
            }
        }
        return $children;
    }

    /**
     * Returns the given service
     *
     * @param string $service
     */
    public function getChild($service): \Sabre\DAV\INode
    {
        $children = $this->getChildren();
        if (isset($children[$service])) {
            return $children[$service];
        }
        throw new \Sabre\DAV\Exception\NotFound($GLOBALS['Language']->getText('plugin_webdav_common', 'service_not_available'));
    }

    public function getName(): string
    {
        return $this->project->getUnixName();
    }

    /**
     * Projects don't have a last modified date
     */
    public function getLastModified(): int
    {
        return 0;
    }
}
