<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\CrossTracker;

use Luracast\Restler\RestException;
use TrackerFactory;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\Tracker\CrossTracker\CrossTrackerReportDao;
use Tuleap\Tracker\CrossTracker\CrossTrackerReportFactory;
use Tuleap\Tracker\CrossTracker\CrossTrackerReportNotFoundException;
use UserManager;

class CrossTrackerReportsResource extends AuthenticatedResource
{
    /**
     * @var CrossTrackerReportFactory
     */
    private $report_factory;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct()
    {
        $this->user_manager   = UserManager::instance();
        $this->report_factory = new CrossTrackerReportFactory(
            new CrossTrackerReportDao(),
            TrackerFactory::instance()
        );
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the report
     */
    public function optionsId($id)
    {
        Header::allowOptionsGet();
    }

    /**
     * Get cross tracker report
     *
     * Get the definition of the given report id
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id Id of the report
     *
     * @return CrossTrackerReportRepresentation
     *
     * @throws 404
     */
    protected function getId($id)
    {
        $this->checkAccess();
        try {
            $current_user   = $this->user_manager->getCurrentUser();
            $report         = $this->report_factory->getById($id, $current_user);
            $representation = new CrossTrackerReportRepresentation();
            $representation->build($report);
        } catch (CrossTrackerReportNotFoundException $exception) {
            throw new RestException(404, "Report $id not found");
        }

        Header::allowOptionsGet();

        return $representation;
    }
}
