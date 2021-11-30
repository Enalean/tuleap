<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\XML\Exporter;

use PFUser;
use Project;
use SimpleXMLElement;
use Tracker;
use Tuleap\Event\Dispatchable;
use Tuleap\Project\XML\Export\ArchiveInterface;

class TrackerEventExportFullXML implements Dispatchable
{
    public const NAME = 'trackerEventExportFullXML';
    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var SimpleXMLElement
     */
    private $xml_element;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var array
     * @psalm-var array<string, Tracker>
     */
    private $exported_trackers;

    /**
     * @var ArchiveInterface
     */
    private $archive;

    /**
     * @psalm-param array<string, Tracker> $exported_trackers
     * @param Tracker[] $exported_trackers
     */
    public function __construct(
        PFUser $user,
        SimpleXMLElement $xml_element,
        Project $project,
        array $exported_trackers,
        ArchiveInterface $archive,
    ) {
        $this->user              = $user;
        $this->xml_element       = $xml_element;
        $this->project           = $project;
        $this->exported_trackers = $exported_trackers;
        $this->archive           = $archive;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }

    public function getXmlElement(): SimpleXMLElement
    {
        return $this->xml_element;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @return Tracker[]
     * @psalm-return array<string, Tracker>
     */
    public function getExportedTrackers(): array
    {
        return $this->exported_trackers;
    }

    public function getArchive(): ArchiveInterface
    {
        return $this->archive;
    }
}
