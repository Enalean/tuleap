<?php
/*
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\XML\Exporter;

use PFUser;
use Project;
use SimpleXMLElement;
use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Tracker;

final class TrackerEventExportStructureXML implements Dispatchable
{
    /**
     * @psalm-param array<string, Tracker> $exported_trackers
     * @param Tracker[] $exported_trackers
     */
    public function __construct(
        private readonly PFUser $user,
        private SimpleXMLElement $xml_element,
        private readonly Project $project,
        private readonly array $exported_trackers,
    ) {
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
}
