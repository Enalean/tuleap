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

namespace Tuleap\Git\Events;

use Project;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Event\Dispatchable;

/**
 * @psalm-immutable
 */
class XMLExportExternalContentEvent implements Dispatchable
{
    public const NAME = 'xmlExportExternalContentEvent';

    /**
     * @var Project
     */
    private $project;

    /**
     * @var SimpleXMLElement
     */
    private $xml_git;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Project $project, SimpleXMLElement $xml_git, LoggerInterface $logger)
    {
        $this->project = $project;
        $this->xml_git = $xml_git;
        $this->logger  = $logger;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getXMLGit(): SimpleXMLElement
    {
        return $this->xml_git;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
