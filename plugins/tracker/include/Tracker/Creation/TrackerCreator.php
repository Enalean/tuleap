<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Tracker\Creation;

use Project;
use Tracker;
use TrackerFactory;
use TrackerFromXmlException;
use TrackerXmlImport;

class TrackerCreator
{
    /**
     * @var TrackerXmlImport
     */
    private $tracker_xml_import;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(
        TrackerXmlImport $tracker_xml_import,
        TrackerFactory $tracker_factory
    ) {
        $this->tracker_xml_import = $tracker_xml_import;
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @throws TrackerFromXmlException
     * @throws \Tracker_Exception
     */
    public function createTrackerFromXml(
        Project $project,
        string $file_path,
        string $name,
        string $description,
        string $itemname
    ): ?Tracker {
        return $this->tracker_xml_import->createFromXMLFileWithInfo(
            $project,
            $file_path,
            $name,
            $description,
            $itemname
        );
    }

    /**
     * @throws TrackerCreationHasFailedException
     * @throws \Tuleap\Tracker\TrackerIsInvalidException
     */
    public function duplicateTracker(
        Project $project,
        string $name,
        string $description,
        string $itemname,
        string $atid_template
    ): Tracker {
        $duplicate = $this->tracker_factory->create(
            $project->getId(),
            -1,
            $atid_template,
            $name,
            $description,
            $itemname
        );

        if (! $duplicate || ! $duplicate['tracker']) {
            throw new TrackerCreationHasFailedException();
        }

        return $duplicate['tracker'];
    }
}
