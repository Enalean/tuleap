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

use Tuleap\REST\Header;
use Luracast\Restler\RestException;
use UserManager;
use PFUser;
use Tracker_ArtifactFactory;
use AgileDashboard_Milestone_Backlog_BacklogItem;
use Tracker_ArtifactDao;
use Tracker_SlicedArtifactsBuilder;
use Tracker_Artifact_PriorityDao;
use Tracker_Artifact_Exception_CannotRankWithMyself;
use Tracker_Artifact;

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

        $artifact                      = $this->getArtifact($id);
        $backlog_items_representations = array();

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
     * Change children order
     *
     * Define the priorities of some children of a given Backlog Item
     *
     * <br>
     * Example:
     * <pre>
     * "order": {
     *   "ids" : [123, 789, 1001],
     *   "direction": "before",
     *   "compared_to": 456
     * }
     * </pre>
     *
     * <br>
     * Resulting order will be: <pre>[…, 123, 789, 1001, 456, …]</pre>
     *
     * @url PATCH {id}/children
     *
     * @param int                                                $id    Id of the Backlog Item
     * @param \Tuleap\AgileDashboard\REST\v1\OrderRepresentation $order Order of the children {@from body}
     *
     * @throws 400
     * @throws 404
     * @throws 409
     */
    protected function patch($id, OrderRepresentation $order) {
        $order->checkFormat($order);

        $artifact = $this->getArtifact($id);
        $user     = $this->getCurrentUser();

        try {
            $order_validator = new OrderValidator($this->getChildrenArtifactIds($user, $artifact));
            $order_validator->validate($order);

            $dao = new Tracker_Artifact_PriorityDao();
            if ($order->direction === OrderRepresentation::BEFORE) {
                $dao->moveListOfArtifactsBefore($order->ids, $order->compared_to);
            } else {
                $dao->moveListOfArtifactsAfter($order->ids, $order->compared_to);
            }
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (OrderIdOutOfBoundException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (Tracker_Artifact_Exception_CannotRankWithMyself $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function getArtifact($id) {
        $artifact = $this->tracker_artifact_factory->getArtifactById($id);

        if (! $artifact) {
            throw new RestException(404, 'Backlog Item not found');
        }

        return $artifact;
    }

    private function getChildrenArtifactIds(PFUser $user, Tracker_Artifact $artifact) {
        $linked_artifacts_index = array();
        foreach ($artifact->getChildrenForUser($user) as $artifact) {
            $linked_artifacts_index[$artifact->getId()] = true;
        }
        return $linked_artifacts_index;
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
        Header::allowOptionsGetPatch();
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function getCurrentUser() {
        return $this->user_manager->getCurrentUser();
    }
}
