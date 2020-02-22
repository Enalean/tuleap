<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

namespace Tuleap\Cardwall\Agiledashboard;

use Planning_Milestone;
use Tuleap\AgileDashboard\Milestone\Pane\PaneInfo;

class CardwallPaneInfo extends PaneInfo
{
    public const IDENTIFIER = 'cardwall';

    public function __construct(Planning_Milestone $milestone)
    {
        parent::__construct($milestone);
    }

    /**
     * @see AgileDashboard_Pane::getIdentifier()
     */
    public function getIdentifier()
    {
        return self::IDENTIFIER;
    }

    /**
     * @see AgileDashboard_Pane::getTitle()
     */
    public function getTitle()
    {
        return 'Card Wall';
    }

    public function getIconName()
    {
        return 'fa-table';
    }
}
