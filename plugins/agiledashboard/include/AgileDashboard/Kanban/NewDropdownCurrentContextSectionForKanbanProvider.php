<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Kanban;

use AgileDashboard_KanbanActionsChecker;
use Tuleap\Kanban\KanbanCannotAccessException;
use Tuleap\Kanban\KanbanNotFoundException;
use Tuleap\AgileDashboard\KanbanUserCantAddArtifactException;
use Tuleap\Layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;

class NewDropdownCurrentContextSectionForKanbanProvider
{
    /**
     * @var TrackerNewDropdownLinkPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var \Tuleap\Kanban\KanbanFactory
     */
    private $kanban_factory;
    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var AgileDashboard_KanbanActionsChecker
     */
    private $kanban_actions_checker;

    public function __construct(
        \Tuleap\Kanban\KanbanFactory $kanban_factory,
        \TrackerFactory $tracker_factory,
        TrackerNewDropdownLinkPresenterBuilder $presenter_builder,
        AgileDashboard_KanbanActionsChecker $kanban_actions_checker,
    ) {
        $this->presenter_builder      = $presenter_builder;
        $this->kanban_factory         = $kanban_factory;
        $this->tracker_factory        = $tracker_factory;
        $this->kanban_actions_checker = $kanban_actions_checker;
    }

    public function getSectionByKanbanId(int $kanban_id, \PFUser $user): ?NewDropdownLinkSectionPresenter
    {
        try {
            $kanban = $this->kanban_factory->getKanban($user, $kanban_id);
            $this->kanban_actions_checker->checkUserCanAddArtifact($user, $kanban);

            $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());
            if (! $tracker) {
                return null;
            }

            return new NewDropdownLinkSectionPresenter(
                dgettext("tuleap-agiledashboard", "Kanban"),
                [
                    $this->presenter_builder->build($tracker),
                ],
            );
        } catch (
            KanbanNotFoundException
            | KanbanCannotAccessException
            | \Kanban_SemanticStatusNotDefinedException
            | \Kanban_TrackerNotDefinedException
            | KanbanUserCantAddArtifactException $exception
        ) {
            return null;
        }
    }
}
