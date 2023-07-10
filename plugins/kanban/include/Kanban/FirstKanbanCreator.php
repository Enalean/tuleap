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

declare(strict_types=1);

namespace Tuleap\Kanban;

use BackendLogger;
use Feedback;
use PFUser;
use Project;
use Tracker;
use Tracker_ReportFactory;
use TrackerFactory;
use TrackerXmlImport;
use Tuleap\Kanban\TrackerReport\TrackerReportUpdater;

final class FirstKanbanCreator
{
    public const ASSIGNED_TO_ME_REPORT = "Assigned to me";

    private string $template_path;
    private string $tracker_itemname;

    public function __construct(
        private readonly Project $project,
        private readonly KanbanManager $kanban_manager,
        private readonly TrackerFactory $tracker_factory,
        private readonly TrackerXmlImport $xml_import,
        private readonly KanbanFactory $kanban_factory,
        private readonly TrackerReportUpdater $tracker_report_updater,
        private readonly Tracker_ReportFactory $report_factory,
    ) {
        $this->template_path    = __DIR__ . '/../../resources/templates/Tracker_activity.xml';
        $this->tracker_itemname = $xml_import->getTrackerItemNameFromXMLFile($this->template_path);
    }

    public function createFirstKanban(PFUser $user): void
    {
        if (! $this->isFirstKanbanNeeded()) {
            return;
        }

        if ($this->isTrackerAlreadyCreated()) {
            $this->warn(
                sprintf(dgettext('tuleap-kanban', 'We tried to create a first kanban for you but an existing tracker (%1$s) prevented it.'), $this->tracker_itemname)
            );
            return;
        }

        $tracker = $this->importTrackerStructure();
        if (! $tracker) {
            $this->warn(dgettext('tuleap-kanban', 'We tried to create a first kanban for you but an internal error prevented it.'));
            return;
        }

        $kanban_id = $this->kanban_manager->createKanban($tracker->getName(), $tracker->getId());
        if (! $kanban_id) {
            $this->warn(dgettext('tuleap-kanban', 'We tried to create a first kanban for you but an internal error prevented it.'));
            return;
        }

        $kanban = $this->kanban_factory->getKanban($user, $kanban_id);
        $this->addAssignedToMeReport($tracker, $kanban);

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            sprintf(
                dgettext('tuleap-kanban', 'We created <a href="%1$s">a first kanban</a> for you. Enjoy!'),
                '/kanban/' . urlencode((string) $kanban_id),
            ),
            CODENDI_PURIFIER_DISABLED
        );
    }

    private function warn(string $message): void
    {
        $GLOBALS['Response']->addFeedback(Feedback::WARN, $message);
    }

    private function importTrackerStructure(): ?Tracker
    {
        try {
            return $this->xml_import->createFromXMLFile($this->project, $this->template_path);
        } catch (\Exception $exception) {
            $logger = BackendLogger::getDefaultLogger();
            $logger->error('Unable to create first kanban for ' . $this->project->getId() . ': ' . $exception->getMessage());
            return null;
        }
    }

    private function isFirstKanbanNeeded(): bool
    {
        $used_trackers = $this->kanban_manager->getTrackersUsedAsKanban($this->project);

        return count($used_trackers) === 0;
    }

    private function isTrackerAlreadyCreated(): bool
    {
        $is_tracker_already_created = $this->tracker_factory->isShortNameExists(
            $this->tracker_itemname,
            (int) $this->project->getId()
        );

        return $is_tracker_already_created;
    }

    private function addAssignedToMeReport(Tracker $tracker, Kanban $kanban): void
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
