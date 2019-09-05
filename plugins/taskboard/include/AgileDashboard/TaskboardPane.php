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

namespace Tuleap\Taskboard\AgileDashboard;

use AgileDashboard_Pane;
use Tuleap\AgileDashboard\Milestone\Pane\PaneInfo;

class TaskboardPane extends AgileDashboard_Pane
{
    /**
     * @var TaskboardPaneInfo
     */
    private $pane_info;

    public function __construct(TaskboardPaneInfo $pane_info)
    {
        $this->pane_info = $pane_info;
    }

    /**
     * @return string eg: 'cardwall'
     * @see PaneInfo::getIdentifier()
     */
    public function getIdentifier()
    {
        return $this->pane_info->getIdentifier();
    }

    /**
     * Return the content when displayed as a Pane
     *
     * @return string eg: '<a href="">customize</a> <table>...</table>'
     */
    public function getFullContent()
    {
        return '';
    }

    /**
     * Return the content when displayed on the agile dashboard front page
     * Only used for cardwall as of today
     *
     * @return string eg: '<table>...</table>'
     */
    public function getMinimalContent()
    {
        return '';
    }
}
