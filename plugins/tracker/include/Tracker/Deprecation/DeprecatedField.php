<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\Deprecation;

use Project;
use Tracker;
use Tracker_FormElement_Field_Computed;

class DeprecatedField
{
    private $project;
    private $tracker;
    private $field;

    public function __construct(Project $project, Tracker $tracker, Tracker_FormElement_Field_Computed $field)
    {
        $this->project = $project;
        $this->tracker = $tracker;
        $this->field   = $field;
    }

    public function getTrackerName()
    {
        return $this->tracker->getName();
    }

    public function getFieldName()
    {
        return $this->field->getName();
    }

    public function getProjectId()
    {
        return $this->project->getId();
    }

    public function getProjectName()
    {
        return $this->project->getPublicName();
    }

    public function getTrackerId()
    {
        return $this->tracker->getId();
    }
}
