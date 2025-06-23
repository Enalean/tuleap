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
use Project;
use ProjectUGroup;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\Timetracking\Admin\TimetrackingEnabler;
use Tuleap\Timetracking\Admin\TimetrackingUgroupSaver;
use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Tracker\Tracker;
use Tuleap\XML\PHPCast;
use UGroupManager;
use User\XML\Import\IFindUserFromXMLReference;
use XML_RNGValidator;

class XMLImport
{
    /**
     * @var XML_RNGValidator
     */
    private $rng_validator;

    /**
     * @var TimetrackingEnabler
     */
    private $timetracking_enabler;

    /**
     * @var TimetrackingUgroupSaver
     */
    private $timetracking_ugroup_saver;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var IFindUserFromXMLReference
     */
    private $user_finder;

    /**
     * @var TimeDao
     */
    private $time_dao;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        XML_RNGValidator $rng_validator,
        TimetrackingEnabler $timetracking_enabler,
        TimetrackingUgroupSaver $timetracking_ugroup_saver,
        UGroupManager $ugroup_manager,
        IFindUserFromXMLReference $user_finder,
        TimeDao $time_dao,
        LoggerInterface $logger,
    ) {
        $this->rng_validator             = $rng_validator;
        $this->timetracking_enabler      = $timetracking_enabler;
        $this->timetracking_ugroup_saver = $timetracking_ugroup_saver;
        $this->ugroup_manager            = $ugroup_manager;
        $this->user_finder               = $user_finder;
        $this->time_dao                  = $time_dao;
        $this->logger                    = $logger;
    }

    public function import(
        SimpleXMLElement $xml,
        Project $project,
        array $created_trackers_objects,
        Tracker_XML_Importer_ArtifactImportedMapping $artifact_id_mapping,
    ): void {
        if (! isset($xml->trackers)) {
            return;
        }

        foreach ($xml->trackers->tracker as $xml_tracker) {
            if (! $xml_tracker->timetracking) {
                continue;
            }

            $xml_timetracking = $xml_tracker->timetracking;
            $this->rng_validator->validate(
                $xml_timetracking,
                __DIR__ . '/../../../resources/timetracking.rng'
            );

            $tracker_xml_id = (string) $xml_tracker['id'];
            if (! isset($created_trackers_objects[$tracker_xml_id])) {
                continue;
            }

            $tracker = $created_trackers_objects[$tracker_xml_id];
            assert($tracker instanceof Tracker);

            $this->enableTimetrackingForTracker($xml_tracker, $tracker);
            $this->importPermissions($xml_timetracking, $project, $tracker);

            if (! $xml_timetracking->time) {
                continue;
            }
            $this->importTimes($xml_timetracking, $artifact_id_mapping);
        }
    }

    private function getUgroup(Project $project, SimpleXMLElement $ugroup): ?ProjectUGroup
    {
        $ugroup_name = (string) $ugroup;
        $ugroup      = $this->ugroup_manager->getUGroupByName($project, $ugroup_name);

        if ($ugroup === null) {
            $this->logger->warning("Could not find any ugroup named $ugroup_name, skipping.");
        }

        return $ugroup;
    }

    /**
     * @throws \User\XML\Import\UserNotFoundException
     */
    private function importTimes(
        SimpleXMLElement $xml_timetracking,
        Tracker_XML_Importer_ArtifactImportedMapping $artifact_id_mapping,
    ): void {
        foreach ($xml_timetracking->time as $xml_time) {
            $time_user = $this->user_finder->getUser(
                $xml_time->user
            );

            $time_artifact_id = (string) $xml_time['artifact_id'];
            if (! $artifact_id_mapping->containsSource($time_artifact_id)) {
                $this->logger->warning("Artifact #$time_artifact_id not found in provided XML, skipping its times.");
                continue;
            }

            $time_day = (new DateTimeImmutable((string) $xml_time->day))->format('Y-m-d');

            $step = '';
            if (isset($xml_time->step)) {
                $step = (string) $xml_time->step;
            }

            $this->time_dao->addTime(
                $time_user->getId(),
                $artifact_id_mapping->get($time_artifact_id),
                $time_day,
                (int) $xml_time->minutes,
                $step
            );
        }
    }

    private function importPermissions(SimpleXMLElement $xml_timetracking, Project $project, Tracker $tracker): void
    {
        if ($xml_timetracking->permissions->read) {
            $reader_ids = [];
            foreach ($xml_timetracking->permissions->read->ugroup as $xml_read_ugroup) {
                $ugroup = $this->getUgroup(
                    $project,
                    $xml_read_ugroup
                );

                if ($ugroup !== null) {
                    $reader_ids[] = $ugroup->getId();
                }
            }

            if (count($reader_ids) > 0) {
                $this->logger->info('Add timetracking reader permission.');
                $this->timetracking_ugroup_saver->saveReaders(
                    $tracker,
                    $reader_ids
                );
            }
        }

        if ($xml_timetracking->permissions->write) {
            $writer_ids = [];
            foreach ($xml_timetracking->permissions->write->ugroup as $xml_write_ugroup) {
                $ugroup = $this->getUgroup(
                    $project,
                    $xml_write_ugroup
                );

                if ($ugroup !== null) {
                    $writer_ids[] = $ugroup->getId();
                }
            }

            if (count($writer_ids) > 0) {
                $this->logger->info('Add timetracking writer permission.');
                $this->timetracking_ugroup_saver->saveWriters(
                    $tracker,
                    $writer_ids
                );
            }
        }
    }

    private function enableTimetrackingForTracker(SimpleXMLElement $xml_tracker, Tracker $tracker): void
    {
        if (PHPCast::toBoolean($xml_tracker->timetracking['is_enabled'])) {
            $xml_tracker_id = (string) $xml_tracker['id'];
            $this->logger->info("Enable timetracking for tracker $xml_tracker_id.");

            $this->timetracking_enabler->enableTimetrackingForTracker($tracker);
        }
    }
}
