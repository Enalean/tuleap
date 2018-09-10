<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

class ArtifactLinksToRenderForPerTrackerTable
{
    /**
     * @var \Tracker
     */
    private $tracker;
    /**
     * @var array
     */
    private $matching_ids;
    /**
     * @var \Tracker_Report_Renderer_Table|null
     */
    private $renderer;

    public function __construct(\Tracker $tracker, array $matching_ids, \Tracker_Report_Renderer_Table $renderer = null)
    {
        $this->tracker      = $tracker;
        $this->matching_ids = $matching_ids;
        $this->renderer     = $renderer;
    }

    /**
     * @return \Tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    /**
     * @return array
     */
    public function getMatchingIDs()
    {
        return $this->matching_ids;
    }

    /**
     * @return null|\Tracker_Report_Renderer_Table
     */
    public function getRenderer()
    {
        return $this->renderer;
    }
}
