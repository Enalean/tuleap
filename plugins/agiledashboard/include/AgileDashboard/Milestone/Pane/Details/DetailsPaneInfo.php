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

namespace Tuleap\AgileDashboard\Milestone\Pane\Details;

use Tuleap\AgileDashboard\Milestone\Pane\PaneInfo;
use Planning_Milestone;

class DetailsPaneInfo extends PaneInfo
{
    const IDENTIFIER = 'details';

    /** @var string */
    private $theme_path;

    public function __construct(Planning_Milestone $milestone, $theme_path)
    {
        parent::__construct($milestone);
        $this->theme_path = $theme_path;
    }

    /**
     * @return string eg: 'cardwall'
     */
    public function getIdentifier()
    {
        return self::IDENTIFIER;
    }

    /**
     * @return string eg: 'Card Wall'
     */
    public function getTitle()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'details_pane_title');
    }

    public function getIconName()
    {
        return 'fa-bar-chart';
    }
}
