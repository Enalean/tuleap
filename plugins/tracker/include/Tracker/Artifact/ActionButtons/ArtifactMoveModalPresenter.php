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

namespace Tuleap\Tracker\Artifact\ActionButtons;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Renderer\ListPickerIncluder;

class ArtifactMoveModalPresenter
{
    /**
     * @var int
     */
    public $tracker_id;
    /**
     * @var string
     */
    public $tracker_name;
    /**
     * @var string
     */
    public $tracker_color;
    /**
     * @var int
     */
    public $artifact_id;
    /**
     * @var int
     */
    public $project_id;
    /**
     * @var string
     */
    public $is_list_picker_enabled;

    public function __construct(Artifact $artifact)
    {
        $this->tracker_id             = $artifact->getTrackerId();
        $this->tracker_name           = $artifact->getTracker()->getItemName();
        $this->tracker_color          = $artifact->getTracker()->getColor()->getName();
        $this->artifact_id            = $artifact->getId();
        $this->project_id             = $artifact->getTracker()->getProject()->getID();
        $this->is_list_picker_enabled = json_encode((bool) \ForgeConfig::get(ListPickerIncluder::FORGE_CONFIG_KEY));
    }
}
