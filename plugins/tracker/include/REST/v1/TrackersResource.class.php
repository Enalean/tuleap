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

/**
 * Wrapper for Tracker related REST methods
 */
class TrackersResource {

    /**
     * Return the definition of the tracker
     *
     * @url GET {id}
     * @param string $id Id of the tracker
     * -- no return defined as restler try to instanciate the corresponding object and it makes api-explorer cry --
     */
    protected function getId($id) {
        $builder = new Tracker_REST_TrackerRestBuilder(Tracker_FormElementFactory::instance());
        $user    = UserManager::instance()->getCurrentUser();
        $tracker = $this->getTrackerById($user, $id);
        return $builder->getTrackerRepresentation($user, $tracker);
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        header('Allow: GET, OPTIONS');
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
        throw new RestException(404);
    }
}

