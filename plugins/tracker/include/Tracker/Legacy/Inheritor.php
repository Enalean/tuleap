<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\Legacy;

use ArtifactTypeFactory;
use ArtifactType;
use TrackerFactory;
use PFUser;
use Project;

class Inheritor
{
    /**
     * @var ArtifactTypeFactory
     */
    private $artifact_type_factory;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(
        ArtifactTypeFactory $artifact_type_factory,
        TrackerFactory $tracker_factory
    ) {
        $this->artifact_type_factory = $artifact_type_factory;
        $this->tracker_factory       = $tracker_factory;
    }

    public function inheritFromLegacy(
        PFUser $user,
        Project $template,
        Project $project
    ) {
        $tv3_to_duplicate = $this->artifact_type_factory->getTrackerTemplatesForNewProjects();

        while ($arr_template = db_fetch_array($tv3_to_duplicate)) {
            $tracker_v3 = new ArtifactType($template, $arr_template['group_artifact_id']);

            $this->tracker_factory->createFromTV3LegacyService($user, $tracker_v3, $project);
        }
    }
}
