<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

use \Tuleap\REST\Header;
use \Tuleap\REST\ProjectAuthorization;
use \Luracast\Restler\RestException;
use \UserManager;
use \Tracker_ArtifactFactory;
use \AgileDashboard_Milestone_Backlog_BacklogItem;
use \Tracker_ArtifactDao;
use \Tracker_SlicedArtifactsBuilder;

/**
 * Wrapper for Backlog_Items related REST methods
 */
class BacklogItemResource {

    const MAX_LIMIT = 100;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var UserManager */
    private $user_manager;

    public function __construct() {
        $this->tracker_artifact_factory = Tracker_ArtifactFactory::instance();
        $this->user_manager             = UserManager::instance();
    }

    /**
     * Get children
     *
     * Get the children of a given Backlog Item
     *
     * @url GET {id}/children
     *
     * @param int $id     Id of the Backlog Item
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentation}
     *
     * @throws 404
     * @throws 406
     */
    protected function getChildren($id, $limit = 10, $offset = 0) {
        $this->checkContentLimit($limit);

        $artifact                      = $this->tracker_artifact_factory->getArtifactById($id);
        $backlog_items_representations = array();

        if (! $artifact) {
            throw new RestException(404, 'Backlog Item not found');
        }

        $sliced_children = $this->getSlicedArtifactsBuilder()->getSlicedChildrenArtifactsForUser($artifact, $this->getCurrentUser(), $limit, $offset);

        foreach ($sliced_children->getArtifacts() as $child) {
            $backlog_item                = new AgileDashboard_Milestone_Backlog_BacklogItem($child);
            $backlog_item_representation = new BacklogItemRepresentation();
            $backlog_item_representation->build($backlog_item);
            $backlog_items_representations[] = $backlog_item_representation;
        }

        $this->sendAllowHeaderForChildren();
        $this->sendPaginationHeaders($limit, $offset, $sliced_children->getTotalSize());

        return $backlog_items_representations;
    }

    /**
     * @url OPTIONS {id}/children
     *
     * @param int $id Id of the BacklogItem
     *
     * @throws 404
     */
    public function optionsChildren($id) {
        $this->sendAllowHeaderForChildren();
    }

    private function getSlicedArtifactsBuilder() {
        return new Tracker_SlicedArtifactsBuilder(new Tracker_ArtifactDao());
    }

    private function checkContentLimit($limit) {
        if (! $this->limitValueIsAcceptable($limit)) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }
    }

    private function limitValueIsAcceptable($limit) {
        return $limit <= self::MAX_LIMIT;
    }

    private function sendAllowHeaderForChildren() {
        Header::allowOptionsGet();
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function getCurrentUser() {
        return $this->user_manager->getCurrentUser();
    }
}
