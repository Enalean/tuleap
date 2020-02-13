<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Event\Events;

use Project;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Event\Dispatchable;
use Tuleap\Project\XML\Export\ArchiveInterface;

class ExportXmlProject implements Dispatchable
{
    public const NAME = 'exportXmlProject';

    /**
     * @var Project
     */
    private $project;
    /**
     * @var array
     */
    private $options;
    /**
     * @var SimpleXMLElement
     */
    private $into_xml;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \UserXMLExporter
     */
    private $user_XML_exporter;
    /**
     * @var ArchiveInterface
     */
    private $archive;
    /**
     * @var string
     */
    private $temporary_dump_path_on_filesystem;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Project $project,
        array $options,
        SimpleXMLElement $into_xml,
        \PFUser $user,
        \UserXMLExporter $user_XML_exporter,
        ArchiveInterface $archive,
        string $temporary_dump_path_on_filesystem,
        LoggerInterface $logger
    ) {
        $this->project                           = $project;
        $this->options                           = $options;
        $this->into_xml                          = $into_xml;
        $this->user                              = $user;
        $this->user_XML_exporter                 = $user_XML_exporter;
        $this->archive                           = $archive;
        $this->temporary_dump_path_on_filesystem = $temporary_dump_path_on_filesystem;
        $this->logger                            = $logger;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getIntoXml(): SimpleXMLElement
    {
        return $this->into_xml;
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }

    public function getUserXMLExporter(): \UserXMLExporter
    {
        return $this->user_XML_exporter;
    }

    public function getArchive(): ArchiveInterface
    {
        return $this->archive;
    }

    public function getTemporaryDumpPathOnFilesystem(): string
    {
        return $this->temporary_dump_path_on_filesystem;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
