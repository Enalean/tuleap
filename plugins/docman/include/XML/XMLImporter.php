<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\XML;

use Docman_ItemFactory;
use Project;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Docman\XML\Import\NodeImporter;

class XMLImporter
{
    /** @var LoggerInterface */
    private $logger;

    /** @var Docman_ItemFactory */
    private $item_factory;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var NodeImporter
     */
    private $node_importer;

    public function __construct(
        Docman_ItemFactory $item_factory,
        Project $project,
        LoggerInterface $logger,
        NodeImporter $node_importer
    ) {
        $this->item_factory  = $item_factory;
        $this->logger        = $logger;
        $this->project       = $project;
        $this->node_importer = $node_importer;
    }

    public function import(SimpleXMLElement $xml_docman, \PFUser $user): void
    {
        $parent_item = $this->item_factory->getRoot($this->project->getGroupId());
        if ($parent_item === null) {
            $this->logger->error('Unable to find a root element in project #' . $this->project->getGroupId());

            return;
        }

        $this->node_importer->import($xml_docman->item, $parent_item, $user);
    }
}
