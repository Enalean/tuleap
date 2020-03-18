<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker;

use Project;
use SimpleXMLElement;
use Tuleap\Event\Dispatchable;

class CreateTrackerFromXMLEvent implements Dispatchable
{
    public const NAME = 'createTrackerFromXMLEvent';

    /**
     * @var Project
     * @psalm-readonly
     */
    private $project;

    /**
     * @var SimpleXMLElement
     */
    private $tracker_xml;

    public function __construct(Project $project, SimpleXMLElement $tracker_xml)
    {
        $this->project     = $project;
        $this->tracker_xml = $tracker_xml;
    }

    /**
     * @psalm-mutation-free
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @psalm-mutation-free
     */
    public function getTrackerXml(): SimpleXMLElement
    {
        return $this->tracker_xml;
    }
}
