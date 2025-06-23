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
 */

declare(strict_types=1);

namespace Tuleap\TestManagement;

use Project;
use Psr\Log\LoggerInterface;
use TrackerXmlImport;
use Tuleap\Tracker\Tracker;

class TestmanagementTrackersCreator
{
    /** @var TrackerXmlImport */
    private $xml_import;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(TrackerXmlImport $xml_import, LoggerInterface $logger)
    {
        $this->xml_import = $xml_import;
        $this->logger     = $logger;
    }

    /**
     * @throws TrackerNotCreatedException
     */
    public function createTrackerFromXML(Project $project, string $tracker_itemname): ?Tracker
    {
        $template_path = (string) realpath(__DIR__ . '/../../resources/templates/Tracker_' . $tracker_itemname . '.xml');
        if ($tracker_itemname === ISSUE_TRACKER_SHORTNAME) {
            $template_path = (string) realpath(__DIR__ . '/../../../tracker/resources/templates/Tracker_Bugs.xml');
        }

        $tracker = $this->importTrackerStructure($project, $template_path);
        if (! $tracker && $tracker_itemname !== ISSUE_TRACKER_SHORTNAME) {
            throw new TrackerNotCreatedException();
        }

        return $tracker;
    }

    private function importTrackerStructure(Project $project, string $template_path): ?Tracker
    {
        $created_tracker = null;
        try {
            $created_tracker = $this->xml_import->createFromXMLFile($project, $template_path);
        } catch (\Exception $exception) {
            $this->logger->error('Unable to create testmanagement config for ' . $project->getId() . ': ' . $exception->getMessage());
        } finally {
            return $created_tracker;
        }
    }
}
