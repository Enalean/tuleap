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
namespace Tuleap\AgileDashboard\REST\v1;

use \Luracast\Restler\RestException;
use \PlanningFactory;
use \Tracker_ArtifactFactory;
use \Tracker_FormElementFactory;
use \TrackerFactory;
use \Planning_MilestoneFactory;
use \Project_AccessProjectNotFoundException;
use \Project_AccessException;
use \UserManager;
use \URLVerification;

/**
 * Wrapper for milestone related REST methods
 */
class MilestoneResource {

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    public function __construct() {
        $this->milestone_factory = new Planning_MilestoneFactory(
            PlanningFactory::build(),
            Tracker_ArtifactFactory::instance(),
            Tracker_FormElementFactory::instance(),
            TrackerFactory::instance()
        );
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        header('Allow: GET, OPTIONS');
    }

    /**
     * Return milestone datas by id if exists
     *
     * @url GET {id}
     * @param string $id ID of the milestone
     * @return MilestoneRepresentation
     *
     * @throws 403
     * @throws 404
     */
    protected function getId($id) {
        $user      = $this->getCurrentUser();
        $milestone = $this->getMilestoneById($user, $id);
        $date      = $milestone->getLastModifiedDate();
        header('Last-Modified: ' . date('c', $date));
        return new MilestoneRepresentation(
            $milestone,
            $this->milestone_factory->getSubMilestones($user, $milestone)
        );
    }

    /**
     * Return info about milestone if exists
     *
     * @url OPTIONS {id}
     * @param string $id ID of the milestone
     *
     * @throws 403
     * @throws 404
     */
    protected function optionsId($id) {
        $milestone = $this->getMilestoneById($this->getCurrentUser(), $id);
        $date      = $milestone->getLastModifiedDate();
        header('Allow: GET, OPTIONS');
        header('Last-Modified: ' . date('c', $date));
    }

    /**
     *
     * @param type $id
     * @return type
     * @throws 403
     * @throws 404
     */
    private function getMilestoneById(\PFUser $user, $id) {
        try {
            $milestone = $this->milestone_factory->getBareMilestoneByArtifactId($user, $id);
            if ($milestone) {
                $project = $milestone->getProject();
                $url_verification = new URLVerification();
                $url_verification->userCanAccessProject($user, $project);
                return $milestone;
            }
        } catch (Project_AccessProjectNotFoundException $exception) {
            throw new RestException(404);
        } catch (Project_AccessException $exception) {
            throw new RestException(403, $exception->getMessage());
        }
        throw new RestException(404);
    }

    private function getCurrentUser() {
        return UserManager::instance()->getCurrentUser();
    }
}
