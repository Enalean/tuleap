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

use Sabre\DAV\ICollection;

/**
 * This class lists the services of a given project that can be accessed using WebDAV
 */
class WebDAVProject implements ICollection
{
    public function __construct(
        private PFUser $user,
        private Project $project,
        private int $maxFileSize,
        private WebDAVUtils $utils,
    ) {
    }

    /**
     * Generates the list of services under the project
     *
     * @return \Sabre\DAV\INode[]
     */
    #[\Override]
    public function getChildren(): array
    {
        $children = [];
        if ($this->project->usesFile()) {
            $children[$GLOBALS['Language']->getText('plugin_webdav_common', 'files')] = new WebDAVFRS($this->user, $this->project, $this->maxFileSize);
        }

        $em    = $this->utils->getEventManager();
        $roots = [];
        $em->processEvent('webdav_root_for_service', ['project' => $this->project,
            'roots'    => &$roots,
        ]);
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
    #[\Override]
    public function getChild($service): \Sabre\DAV\INode
    {
        $children = $this->getChildren();
        if (isset($children[$service])) {
            return $children[$service];
        }
        throw new \Sabre\DAV\Exception\NotFound($GLOBALS['Language']->getText('plugin_webdav_common', 'service_not_available'));
    }

    #[\Override]
    public function getName(): string
    {
        return $this->project->getUnixName();
    }

    /**
     * Projects don't have a last modified date
     */
    #[\Override]
    public function getLastModified(): int
    {
        return 0;
    }

    #[\Override]
    public function delete(): void
    {
        throw new \Sabre\DAV\Exception\NotFound('Operation not supported');
    }

    #[\Override]
    public function setName($name): void
    {
        throw new \Sabre\DAV\Exception\NotFound('Operation not supported');
    }

    #[\Override]
    public function createFile($name, $data = null): void
    {
        throw new \Sabre\DAV\Exception\NotFound('Operation not supported');
    }

    #[\Override]
    public function createDirectory($name): void
    {
        throw new \Sabre\DAV\Exception\NotFound('Operation not supported');
    }

    #[\Override]
    public function childExists($name): bool
    {
        try {
            $this->getChild($name);
            return true;
        } catch (\Sabre\DAV\Exception) {
        }
        return false;
    }
}
