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
use PFUser;
use Planning_Milestone;
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
     * @var null|\Closure(): AgileDashboard_Pane
     */
    private $active_pane_builder;
    /**
     * @var ActivePaneContext|null
     */
    private $active_pane_context;
    /**
     * @var PFUser
     */
    private $current_user;

    public function __construct(
        Planning_Milestone $milestone,
        ?ActivePaneContext $active_pane_context,
        array $panes,
        ?AgileDashboard_Pane $active_pane,
        PFUser $current_user
    ) {
        $this->milestone           = $milestone;
        $this->active_pane_context = $active_pane_context;
        $this->panes               = $panes;
        $this->active_pane         = $active_pane;
        $this->current_user        = $current_user;
    }

    public function getPanes(): array
    {
        $internal_links = [];
        $external_links = [];
        foreach ($this->panes as $pane) {
            if ($pane->isExternalLink()) {
                $external_links[] = $pane;
            } else {
                $internal_links[] = $pane;
            }
        }

        return array_merge($internal_links, $external_links);
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
        if ($this->active_pane_builder) {
            return call_user_func($this->active_pane_builder);
        }

        return $this->active_pane;
    }

    /**
     * @param \Closure(): AgileDashboard_Pane $active_pane_builder
     */
    public function setActivePaneBuilder(\Closure $active_pane_builder): void
    {
        $this->active_pane_builder = $active_pane_builder;
    }

    public function getMilestone(): Planning_Milestone
    {
        return $this->milestone;
    }

    public function getActivePaneContext(): ?ActivePaneContext
    {
        return $this->active_pane_context;
    }

    public function getCurrentUser(): PFUser
    {
        return $this->current_user;
    }
}
