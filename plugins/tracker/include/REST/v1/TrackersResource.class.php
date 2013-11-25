<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use \Luracast\Restler\RestException;
use \Tracker_REST_TrackerRestBuilder;
use \Tracker_FormElementFactory;
use \TrackerFactory;
use \URLVerification;
use \UserManager;
use \Tuleap\REST\Header;

/**
 * Wrapper for Tracker related REST methods
 */
class TrackersResource {

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptions();
    }

    /**
     * @url OPTIONS {id}
     *
     * @param string $id Id of the tracker
     */
    protected function optionsId($id) {
        $user    = UserManager::instance()->getCurrentUser();
        $this->getTrackerById($user, $id);
        $this->sendAllowHeaderForTracker();
    }

    /**
     * Get tracker
     *
     * Get the definition of the given tracker
     *
     * @url GET {id}
     *
     * @param int $id Id of the tracker
     *
     * @return TrackerRepresentation
     */
    protected function getId($id) {
        $builder = new Tracker_REST_TrackerRestBuilder(Tracker_FormElementFactory::instance());
        $user    = UserManager::instance()->getCurrentUser();
        $tracker = $this->getTrackerById($user, $id);
        $this->sendAllowHeaderForTracker();

        return $builder->getTrackerRepresentation($user, $tracker);
    }

    private function getTrackerById(\PFUser $user, $id) {
        try {
            $tracker = TrackerFactory::instance()->getTrackerById($id);
            if ($tracker) {
                if ($tracker->userCanView($user)) {
                    $url_verification = new URLVerification();
                    $url_verification->userCanAccessProject($user, $tracker->getProject());
                    return $tracker;
                }
                throw new RestException(403);
            }
            throw new RestException(404);
        } catch (\Project_AccessProjectNotFoundException $exception) {
            throw new RestException(404);
        } catch (\Project_AccessException $exception) {
            throw new RestException(403, $exception->getMessage());
        }
    }

    private function sendAllowHeaderForTracker() {
        Header::allowOptionsGet();
    }
}

