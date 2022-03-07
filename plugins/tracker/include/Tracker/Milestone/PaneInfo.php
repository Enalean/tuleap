<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Milestone;

/**
 * An agile dashboard can have several panes (MilestonePlanning, Burndown, Cardwall)
 * Each Pane is associated to a PaneInfo that describe the Pane (it's used to
 * display presence of a Pane.
 * It's meant to be as lightweight as possible as it is required to build the view
 * regardless of what we want to display.
 */
abstract class PaneInfo
{
    public const ACTION = 'show';

    private bool $is_active = false;

    protected string $action = self::ACTION;

    public function __construct()
    {
    }

    /**
     * Return true if the current Pane is selected
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * @return bool
     */
    public function isExternalLink()
    {
        return false;
    }

    /**
     * Set activation
     */
    public function setActive(bool $state): void
    {
        $this->is_active = $state;
    }

    abstract public function getUri(): string;

    /**
     * Technical identifier for HTML output
     *
     * @return string eg: 'cardwall'
     */
    abstract public function getIdentifier();

    /**
     * @return string eg: 'Card Wall'
     */
    abstract public function getTitle();

    /**
     * @return string eg: 'fa-table'
     */
    abstract public function getIconName();
}
