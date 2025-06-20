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

namespace Tuleap\Timetracking\XML;

use DateTimeImmutable;
use PFUser;
use SimpleXMLElement;
use Tracker_ArtifactFactory;
use Tuleap\Timetracking\Admin\TimetrackingEnabler;
use Tuleap\Timetracking\Admin\TimetrackingUgroupRetriever;
use Tuleap\Timetracking\Time\TimeRetriever;
use Tuleap\Tracker\Tracker;
use Tuleap\xml\XMLDateHelper;
use UserManager;
use UserXMLExporter;
use XML_SimpleXMLCDATAFactory;

class XMLExport
{
    /**
     * @var TimetrackingEnabler
     */
    private $timetracking_enabler;

    /**
     * @var TimetrackingUgroupRetriever
     */
    private $timetracking_ugroup_retriever;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var TimeRetriever
     */
    private $time_retriever;

    /**
     * @var UserXMLExporter
     */
    private $user_xml_exporter;

    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        TimetrackingEnabler $timetracking_enabler,
        TimetrackingUgroupRetriever $timetracking_ugroup_retriever,
        Tracker_ArtifactFactory $artifact_factory,
        TimeRetriever $time_retriever,
        UserXMLExporter $user_xml_exporter,
        UserManager $user_manager,
    ) {
        $this->timetracking_enabler          = $timetracking_enabler;
        $this->timetracking_ugroup_retriever = $timetracking_ugroup_retriever;
        $this->artifact_factory              = $artifact_factory;
        $this->time_retriever                = $time_retriever;
        $this->user_xml_exporter             = $user_xml_exporter;
        $this->user_manager                  = $user_manager;
    }

    public function export(
        SimpleXMLElement $xml,
        PFUser $user,
        array $exported_trackers,
    ): void {
        if (! isset($xml->trackers)) {
            return;
        }

        foreach ($xml->trackers->tracker as $xml_tracker) {
            assert($xml_tracker instanceof SimpleXMLElement);

            $xml_tracker_id = (string) $xml_tracker['id'];
            if (! isset($exported_trackers[$xml_tracker_id])) {
                continue;
            }

            $exported_tracker = $exported_trackers[$xml_tracker_id];
            assert($exported_tracker instanceof Tracker);

            if (! $this->timetracking_enabler->isTimetrackingEnabledForTracker($exported_tracker)) {
                continue;
            }

            $xml_timetracking = $xml_tracker->addChild('timetracking');
            $xml_timetracking->addAttribute('is_enabled', '1');

            $this->exportPermissions($xml_timetracking, $exported_tracker);
            $this->exportTimes($exported_tracker, $user, $xml_timetracking);
        }
    }

    private function exportPermissions(SimpleXMLElement $xml_timetracking, Tracker $exported_tracker): void
    {
        $xml_timetracking_permissions = $xml_timetracking->addChild('permissions');

        $reader_ugroups = $this->timetracking_ugroup_retriever->getReaderUgroupsForTracker($exported_tracker);
        if (count($reader_ugroups) > 0) {
            $xml_timetracking_permission_read = $xml_timetracking_permissions->addChild('read');
            foreach ($reader_ugroups as $reader_ugroup) {
                $cdata = new XML_SimpleXMLCDATAFactory();
                $cdata->insert($xml_timetracking_permission_read, 'ugroup', $reader_ugroup->getNormalizedName());
            }
        }

        $writer_ugroups = $this->timetracking_ugroup_retriever->getWriterUgroupsForTracker($exported_tracker);
        if (count($writer_ugroups) > 0) {
            $xml_timetracking_permission_write = $xml_timetracking_permissions->addChild('write');
            foreach ($writer_ugroups as $writer_ugroup) {
                $cdata = new XML_SimpleXMLCDATAFactory();
                $cdata->insert($xml_timetracking_permission_write, 'ugroup', $writer_ugroup->getNormalizedName());
            }
        }
    }

    private function exportTimes(Tracker $exported_tracker, PFUser $user, SimpleXMLElement $xml_timetracking): void
    {
        foreach ($this->artifact_factory->getArtifactsByTrackerId($exported_tracker->getId()) as $artifact) {
            $times = $this->time_retriever->getTimesForUser(
                $user,
                $artifact
            );

            foreach ($times as $time) {
                $time_user = $this->user_manager->getUserById($time->getUserId());
                if ($time_user === null) {
                    continue;
                }

                $xml_time = $xml_timetracking->addChild('time');
                $xml_time->addAttribute('artifact_id', (string) $time->getArtifactId());
                $this->user_xml_exporter->exportUser(
                    $time_user,
                    $xml_time,
                    'user'
                );
                $xml_time->addChild('minutes', (string) $time->getMinutes());
                $xml_time->addChild('step', $time->getStep());
                XMLDateHelper::addChild(
                    $xml_time,
                    'day',
                    new DateTimeImmutable($time->getDay())
                );
            }
        }
    }
}
