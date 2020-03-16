<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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


namespace Tuleap\AgileDashboard\Milestone\Pane;

use AgileDashboard_Pane;
use Codendi_Request;
use PFUser;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Tuleap\Event\Dispatchable;

class PaneInfoCollector implements Dispatchable
{
    public const NAME = "agiledashboardEventAdditionalPanesOnMilestone";

    /**
     * @var PaneInfo[]
     */
    private $panes;

    /**
     * @var AgileDashboard_Pane|null
     */
    private $active_pane;
    /**
     * @var Planning_Milestone
     */
    private $milestone;
    /**
     * @var Codendi_Request
     */
    private $request;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    public function __construct(
        Planning_Milestone $milestone,
        Codendi_Request $request,
        PFUser $user,
        Planning_MilestoneFactory $milestone_factory,
        array $panes,
        ?AgileDashboard_Pane $active_pane
    ) {
        $this->milestone         = $milestone;
        $this->request           = $request;
        $this->user              = $user;
        $this->milestone_factory = $milestone_factory;
        $this->panes             = $panes;
        $this->active_pane       = $active_pane;
    }

    public function getPanes(): array
    {
        return $this->panes;
    }

    public function addPane(PaneInfo $pane): void
    {
        $this->panes[$pane->getIdentifier()] = $pane;
    }

    public function has(string $pane_identifier): bool
    {
        foreach ($this->panes as $pane) {
            if ($pane->getIdentifier() === $pane_identifier) {
                return true;
            }
        }

        return false;
    }

    public function addPaneAfter(string $sibling_identifier, PaneInfo $pane): void
    {
        $sibling_position = 0;
        foreach ($this->panes as $sibling_pane) {
            $sibling_position++;
            if ($sibling_identifier === $sibling_pane->getIdentifier()) {
                break;
            }
        }

        $this->panes = array_merge(
            array_slice($this->panes, 0, $sibling_position),
            [$pane->getIdentifier() => $pane],
            array_slice($this->panes, $sibling_position)
        );
    }

    public function getActivePane(): ?AgileDashboard_Pane
    {
        return $this->active_pane;
    }

    public function setActivePane(AgileDashboard_Pane $active_pane): void
    {
        $this->active_pane = $active_pane;
    }

    public function getMilestone(): Planning_Milestone
    {
        return $this->milestone;
    }

    public function getRequest(): Codendi_Request
    {
        return $this->request;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }

    public function getMilestoneFactory(): Planning_MilestoneFactory
    {
        return $this->milestone_factory;
    }
}
