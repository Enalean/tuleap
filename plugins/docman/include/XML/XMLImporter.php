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
use Tuleap\Docman\XML\Import\PostFolderImporter;
use XML_RNGValidator;

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
    /**
     * @var XML_RNGValidator
     */
    private $rng_validator;

    public function __construct(
        Docman_ItemFactory $item_factory,
        Project $project,
        LoggerInterface $logger,
        NodeImporter $node_importer,
        XML_RNGValidator $rng_validator
    ) {
        $this->item_factory  = $item_factory;
        $this->logger        = $logger;
        $this->project       = $project;
        $this->node_importer = $node_importer;
        $this->rng_validator = $rng_validator;
    }

    public function import(SimpleXMLElement $xml_docman): void
    {
        $this->rng_validator->validate(
            $xml_docman,
            __DIR__ . '/../../resources/docman.rng'
        );

        $parent_item = $this->item_factory->getRoot($this->project->getGroupId());
        if ($parent_item === null) {
            $this->logger->error('Unable to find a root element in project #' . $this->project->getGroupId());

            return;
        }

        $folder_importer = new PostFolderImporter();
        $folder_importer->postImport($this->node_importer, $xml_docman->item, $parent_item);
    }
}
