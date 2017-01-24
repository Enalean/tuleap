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

use Tuleap\Tracker\Deprecation\Dao;
use ProjectManager;
use TrackerFactory;
use Tracker_FormElementFactory;
use Tracker;
use Project;
use PFUser;
use Tracker_FormElement_Field_Computed;

class DeprecationRetriever
{
    private $dao;
    private $project_manager;
    private $tracker_factory;
    private $tracker_formelement_factory;
    private $user_preferences_dao;

    public function __construct(
        Dao $dao,
        ProjectManager $project_manager,
        TrackerFactory $tracker_factory,
        Tracker_FormElementFactory $tracker_formelement_factory
    ) {
        $this->dao                         = $dao;
        $this->project_manager             = $project_manager;
        $this->tracker_factory             = $tracker_factory;
        $this->tracker_formelement_factory = $tracker_formelement_factory;
    }

    public function isWarningDeprecatedFieldHidden(PFUser $user, TRacker $tracker)
    {
        $preference = $user->getPreference($tracker->getHideWarningPreferenceName());

        return ($preference) ? $preference >= $_SERVER['REQUEST_TIME'] : false;
    }

    public function getDeprecatedTrackersFields()
    {
        $deprecated_fields = array();
        foreach ($this->dao->searchDeprecatedTrackersFields() as $row) {
            $deprecated_fields[] = $this->instantiateFromRow($row);
        }

        return $deprecated_fields;
    }

    public function getDeprecatedTrackersFieldsByProject(Project $project)
    {
        $deprecated_fields = array();
        foreach ($this->dao->searchDeprecatedTrackersFieldsByProject($project->getID()) as $row) {
            $deprecated_fields[] = $this->instantiateFromRow($row);
        }

        return $deprecated_fields;
    }

    public function getDeprecatedFieldsByTracker(Tracker $tracker)
    {
        $deprecated_fields = array();
        foreach ($this->dao->searchDeprecatedFieldsByTracker($tracker->getId()) as $row) {
            $deprecated_fields[] = $this->instantiateFromRow($row);
        }

        return $deprecated_fields;
    }

    public function instantiateFromRow(array $row)
    {
        $project = $this->project_manager->getProject($row['group_id']);
        $tracker = $this->tracker_factory->getTrackerById($row['tracker_id']);
        $field   = $this->tracker_formelement_factory->getFormElementFieldById($row['field_id']);

        return new DeprecatedField($project, $tracker, $field);
    }

    public function isALegacyField(Tracker_FormElement_Field_Computed $field)
    {
        return false;
    }
}
