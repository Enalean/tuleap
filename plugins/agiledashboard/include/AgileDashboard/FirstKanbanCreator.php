<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\Kanban\TrackerReport\TrackerReportUpdater;
use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\KanbanFactory;

class AgileDashboard_FirstKanbanCreator
{
    public const ASSIGNED_TO_ME_REPORT = "Assigned to me";

    /** @var Project */
    private $project;

    /** @var AgileDashboard_KanbanManager */
    private $kanban_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var TrackerXmlImport */
    private $xml_import;

    /** @var string */
    private $template_path;

    /** @var string */
    private $tracker_itemname;

    /**
     * @var TrackerReportUpdater
     */
    private $tracker_report_updater;

    /**
     * @var KanbanFactory
     */
    private $kanban_factory;

    /**
     * @var Tracker_ReportFactory
     */
    private $report_factory;

    public function __construct(
        Project $project,
        AgileDashboard_KanbanManager $kanban_manager,
        TrackerFactory $tracker_factory,
        TrackerXmlImport $xml_import,
        KanbanFactory $kanban_factory,
        TrackerReportUpdater $tracker_report_updater,
        Tracker_ReportFactory $report_factory,
    ) {
        $this->project                = $project;
        $this->kanban_manager         = $kanban_manager;
        $this->tracker_factory        = $tracker_factory;
        $this->xml_import             = $xml_import;
        $this->template_path          = __DIR__ . '/../../resources/templates/Tracker_activity.xml';
        $this->tracker_itemname       = $this->xml_import->getTrackerItemNameFromXMLFile($this->template_path);
        $this->tracker_report_updater = $tracker_report_updater;
        $this->kanban_factory         = $kanban_factory;
        $this->report_factory         = $report_factory;
    }

    public function createFirstKanban(PFUser $user)
    {
        if (! $this->isFirstKanbanNeeded()) {
            return;
        }

        if ($this->isTrackerAlreadyCreated()) {
            $this->warn(
                sprintf(dgettext('tuleap-agiledashboard', 'We tried to create a first kanban for you but an existing tracker (%1$s) prevented it.'), $this->tracker_itemname)
            );
            return;
        }

        $tracker = $this->importTrackerStructure();
        if (! $tracker) {
            $this->warn(dgettext('tuleap-agiledashboard', 'We tried to create a first kanban for you but an internal error prevented it.'));
            return;
        }

        $kanban_id = $this->kanban_manager->createKanban($tracker->getName(), $tracker->getId());
        if (! $kanban_id) {
            $this->warn(dgettext('tuleap-agiledashboard', 'We tried to create a first kanban for you but an internal error prevented it.'));
            return;
        }

        $kanban = $this->kanban_factory->getKanban($user, $kanban_id);
        $this->addAssignedToMeReport($tracker, $kanban);

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            sprintf(dgettext('tuleap-agiledashboard', 'We created <a href="%1$s">a first kanban</a> for you. Enjoy!'), '?' . http_build_query(
                [
                    'group_id' => $this->project->getId(),
                    'action'   => 'showKanban',
                    'id'       => $kanban_id,
                ]
            )),
            CODENDI_PURIFIER_DISABLED
        );
    }

    private function warn($message)
    {
        $GLOBALS['Response']->addFeedback(Feedback::WARN, $message);
    }

    /** @return Tracker|null */
    private function importTrackerStructure()
    {
        try {
            return $this->xml_import->createFromXMLFile($this->project, $this->template_path);
        } catch (Exception $exception) {
            $logger = BackendLogger::getDefaultLogger();
            $logger->error('Unable to create first kanban for ' . $this->project->getId() . ': ' . $exception->getMessage());
            return;
        }
    }

    private function isFirstKanbanNeeded()
    {
        $used_trackers = $this->kanban_manager->getTrackersUsedAsKanban($this->project);

        return count($used_trackers) === 0;
    }

    private function isTrackerAlreadyCreated()
    {
        $is_tracker_already_created = $this->tracker_factory->isShortNameExists(
            $this->tracker_itemname,
            $this->project->getId()
        );

        return $is_tracker_already_created;
    }

    private function addAssignedToMeReport(Tracker $tracker, Kanban $kanban)
    {
        $reports = $this->report_factory->getReportsByTrackerId(
            $tracker->getId(),
            null
        );

        foreach ($reports as $tracker_report) {
            if ($tracker_report->isPublic() && $tracker_report->getName() === self::ASSIGNED_TO_ME_REPORT) {
                $this->tracker_report_updater->save($kanban, [(int) $tracker_report->getId()]);

                return;
            }
        }
    }
}
