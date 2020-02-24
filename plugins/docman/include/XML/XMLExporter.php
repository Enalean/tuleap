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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Docman\XML;

use Docman_ItemFactory;
use Docman_VersionFactory;
use Project;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Docman\XML\Export\PermissionsExporter;
use Tuleap\Docman\XML\Export\XMLExportVisitor;
use Tuleap\Project\XML\Export\ArchiveInterface;
use UserManager;
use UserXMLExporter;

class XMLExporter
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Docman_ItemFactory
     */
    private $item_factory;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var Docman_VersionFactory
     */
    private $version_factory;
    /**
     * @var UserXMLExporter
     */
    private $user_exporter;
    /**
     * @var PermissionsExporter
     */
    private $permissions_exporter;

    public function __construct(
        LoggerInterface $logger,
        Docman_ItemFactory $item_factory,
        Docman_VersionFactory $version_factory,
        UserManager $user_manager,
        UserXMLExporter $user_exporter,
        PermissionsExporter $permissions_exporter
    ) {
        $this->logger               = $logger;
        $this->item_factory         = $item_factory;
        $this->user_manager         = $user_manager;
        $this->version_factory      = $version_factory;
        $this->user_exporter        = $user_exporter;
        $this->permissions_exporter = $permissions_exporter;
    }

    public function export(Project $project, SimpleXMLElement $xml, ArchiveInterface $archive): void
    {
        $root = $this->item_factory->getRoot($project->getGroupId());
        if (! $root) {
            return;
        }

        $docman = $xml->addChild('docman');
        $user   = $this->user_manager->getCurrentUser();
        $tree   = $this->item_factory->getItemSubTree($root, $user, true, true);
        assert($tree !== null);

        $export = new XMLExportVisitor(
            $this->logger,
            $archive,
            $this->version_factory,
            $this->user_exporter,
            $this->permissions_exporter
        );
        $export->export($docman, $tree);
    }
}
