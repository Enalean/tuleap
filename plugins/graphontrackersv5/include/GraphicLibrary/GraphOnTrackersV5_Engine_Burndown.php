<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\GraphOnTrackersV5\GraphicLibrary;

use Tracker_Chart_Burndown;
use Tuleap\Chart\Chart;

class GraphOnTrackersV5_Engine_Burndown extends GraphOnTrackersV5_Engine
{
    public $duration;
    public $start_date;
    public string $title       = '';
    public string $description = '';
    public int $width          = 0;
    public int $height         = 0;

    #[\Override]
    public function validData(): bool
    {
        if ($this->duration && $this->duration > 1) {
            return true;
        } else {
            echo " <p class='feedback_info'>" . sprintf(dgettext('tuleap-graphontrackersv5', 'No datas to display for graph %1$s'), $this->title) . '</p>';
            return false;
        }
    }

    /**
     * @return Chart
     */
    #[\Override]
    public function buildGraph()
    {
        $burndown = new Tracker_Chart_Burndown($this->data);
        $burndown->setTitle($this->title);
        $burndown->setDescription($this->description);
        $burndown->setWidth($this->width);
        $burndown->setHeight($this->height);
        $burndown->setDuration($this->duration);

        $this->graph = $burndown->buildGraph();
        return $this->graph;
    }
}
