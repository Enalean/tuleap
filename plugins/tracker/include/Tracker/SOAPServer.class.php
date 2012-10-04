<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'Report/Tracker_Report_SOAP.class.php';

class Tracker_SOAPServer {
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * @var Tracker_ReportDao
     */
    private $report_dao;

    public function __construct(
            UserManager $user_manager,
            TrackerFactory $tracker_factory,
            PermissionsManager $permissions_manager,
            Tracker_ReportDao $dao,
            Tracker_FormElementFactory $formelement_factory) {
        $this->user_manager        = $user_manager;
        $this->tracker_factory     = $tracker_factory;
        $this->permissions_manager = $permissions_manager;
        $this->report_dao          = $dao;
        $this->formelement_factory = $formelement_factory;
    }

    public function getArtifacts($session_key, $group_id, $tracker_id, $criteria, $offset, $max_rows) {
        $current_user = $this->user_manager->getCurrentUser($session_key);
        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        $report = new Tracker_Report_SOAP($current_user, $tracker, $this->permissions_manager, $this->report_dao, $this->formelement_factory);
        $report->setSoapCriteria($criteria);
        $matching = $report->getMatchingIds();
        return explode(',', $matching['id']);
    }
}

?>
