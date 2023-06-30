<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban;

use EventManager;
use TrackerFactory;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Tracker\TrackerCrumbInContext;
use Tuleap\Tracker\TrackerCrumbLinkInContext;

final class BreadCrumbBuilder
{
    private const CRUMB_IDENTIFIER = 'kanban';

    public function __construct(
        private readonly TrackerFactory $tracker_factory,
        private readonly \Tuleap\Kanban\KanbanFactory $kanban_factory,
    ) {
    }

    /**
     * @throws \Tuleap\Kanban\KanbanCannotAccessException
     * @throws \Tuleap\Kanban\KanbanNotFoundException
     */
    public function build(\PFUser $current_user, int $kanban_id): BreadCrumb
    {
        $kanban  = $this->kanban_factory->getKanban($current_user, $kanban_id);
        $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
        if ($tracker && $tracker->userCanView($current_user)) {
            $tracker_crumb = EventManager::instance()->dispatch(new TrackerCrumbInContext($tracker, $current_user));
            return $tracker_crumb->getCrumb(self::CRUMB_IDENTIFIER);
        }
        throw new KanbanNotFoundException();
    }

    public function addKanbanCrumb(TrackerCrumbInContext $tracker_crumb): void
    {
        $tracker   = $tracker_crumb->getTracker();
        $kanban_id = $this->kanban_factory->getKanbanIdByTrackerId($tracker_crumb->getTracker()->getId());
        if ($kanban_id) {
            try {
                $kanban = $this->kanban_factory->getKanban($tracker_crumb->getUser(), $kanban_id);
                $tracker_crumb->addGoToLink(
                    self::CRUMB_IDENTIFIER,
                    new TrackerCrumbLinkInContext(
                        $kanban->getName(),
                        sprintf(dgettext('tuleap-kanban', '%s Kanban'), $kanban->getName()),
                        AGILEDASHBOARD_BASE_URL . '?' . http_build_query(
                            [
                                'group_id' => $tracker->getProject()->getID(),
                                'action'   => 'showKanban',
                                'id'       => $kanban_id,
                            ]
                        )
                    )
                );
            } catch (\Tuleap\Kanban\KanbanCannotAccessException $e) {
            } catch (\Tuleap\Kanban\KanbanNotFoundException $e) {
            }
        }
    }
}
