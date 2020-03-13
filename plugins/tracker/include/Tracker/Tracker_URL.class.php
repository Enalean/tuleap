<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

class Tracker_URL extends URL
{

    /**
     * Return the Tracker object that correspond to the given request
     *
     * @param Codendi_Request $request The request
     * @param PFUser            $user    Who access the request
     *
     * @return Tracker_Dispatchable_Interface
     */
    public function getDispatchableFromRequest(Codendi_Request $request, PFUser $user)
    {
        if ((int) $request->get('aid')) {
            if ($artifact = $this->getArtifactFactory()->getArtifactByid($request->get('aid'))) {
                return $artifact;
            } else {
                throw new Tracker_ResourceDoesntExistException($GLOBALS['Language']->getText('plugin_tracker_common_type', 'artifact_not_exist'));
            }
        } elseif ((int) $request->get('report')) {
            $store_in_session = true;
            if ($request->exist('store_in_session')) {
                $store_in_session = (bool) $request->get('store_in_session');
            }
            if ($report = $this->getArtifactReportFactory()->getReportById($request->get('report'), $user->getId(), $store_in_session)) {
                return $report;
            } else {
                throw new Tracker_ResourceDoesntExistException($GLOBALS['Language']->getText('plugin_tracker_common_type', 'report_not_exist'));
            }
        } elseif ((int) $request->get('tracker') || (int) $request->get('atid')) {
            $tracker_id = (int) $request->get('tracker');
            if (!$tracker_id) {
                $tracker_id = (int) $request->get('atid');
            }
            if (($tracker = $this->getTrackerFactory()->getTrackerByid($tracker_id))) {
                return $tracker;
            } else {
                throw new Tracker_ResourceDoesntExistException($GLOBALS['Language']->getText('plugin_tracker_common_type', 'tracker_not_exist'));
            }
        } elseif ((int) $request->get('formElement')) {
            if ($formElement = $this->getTracker_FormElementFactory()->getFormElementByid($request->get('formElement'))) {
                return $formElement;
            }
        } elseif ($request->get('func') == 'new-artifact-link') {
            if ($artifact = Tracker_ArtifactFactory::instance()->getArtifactByid($request->get('id'))) {
                return $artifact;
            } else {
                throw new Tracker_ResourceDoesntExistException($GLOBALS['Language']->getText('plugin_tracker_common_type', 'artifact_not_exist'));
            }
        } elseif ((int) $request->get('link-artifact-id')) {
            if ($artifact = Tracker_ArtifactFactory::instance()->getArtifactByid($request->get('link-artifact-id'))) {
                return $artifact;
            } else {
                throw new Tracker_ResourceDoesntExistException($GLOBALS['Language']->getText('plugin_tracker_common_type', 'artifact_not_exist'));
            }
        }
        throw new Tracker_NoMachingResourceException();
    }

    /**
     * Return the project ID of the ressource of the current request
     *
     * @see src/common/include/URL::getGroupIdFromUrl()
     *
     * @param Array $requestUri $SERVER['REQUEST_URI']
     *
     * @return int
     */
    public function getGroupIdFromUrl($requestUri)
    {
        $request = HTTPRequest::instance();
        $user    = UserManager::instance()->getCurrentUser();

        try {
            $object = $this->getDispatchableFromRequest($request, $user);
            if ($object instanceof Tracker) {
                $tracker = $object;
            } else {
                // All other objects have a "getTracker" method
                $tracker = $object->getTracker();
            }
            if ($tracker) {
                return $tracker->getGroupId();
            }
        } catch (Exception $e) {
            // Do nothing
        }

        // If no valid group id found force return of a fake group id
        return -1;
    }

    /**
     * @return TrackerFactory
     */
    protected function getTrackerFactory()
    {
        return TrackerFactory::instance();
    }

    protected function getTracker_FormElementFactory()
    {
        return Tracker_FormElementFactory::instance();
    }

    protected function getArtifactFactory()
    {
        return Tracker_ArtifactFactory::instance();
    }

    protected function getArtifactReportFactory()
    {
        return Tracker_ReportFactory::instance();
    }
}
