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

declare(strict_types=1);

namespace Tuleap\Docman\XML\Import;

use Docman_Item;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\xml\InvalidDateException;
use User\XML\Import\UserNotFoundException;

class PostFileImporter implements PostImporter
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var VersionImporter
     */
    private $version_importer;

    public function __construct(
        VersionImporter $version_importer,
        LoggerInterface $logger
    ) {
        $this->version_importer = $version_importer;
        $this->logger           = $logger;
    }

    public function postImport(NodeImporter $node_importer, SimpleXMLElement $node, Docman_Item $item): void
    {
        $version_number = 1;
        foreach ($node->versions->version as $version) {
            $this->logger->debug("└ Importing version #$version_number");
            try {
                $this->version_importer->import($version, $item, $version_number);
            } catch (UnableToCreateFileOnFilesystemException | UnableToCreateVersionInDbException | InvalidDateException | UserNotFoundException $exception) {
                $this->logger->error($exception->getMessage());
            }
            $version_number++;
        }
    }
}
