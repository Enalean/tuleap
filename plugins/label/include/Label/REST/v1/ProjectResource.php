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

namespace Tuleap\Label\REST\v1;

use EventManager;
use Luracast\Restler\RestException;
use Project;
use ProjectManager;
use Tuleap\Label\LabeledItemCollection;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;
use URLVerification;
use UserManager;

class ProjectResource
{
    public const MAX_LIMIT = 50;

    /** @var UserManager */
    private $user_manager;

    /** @var EventManager */
    private $event_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var QueryParameterParser */
    private $query_parser;

    public function __construct()
    {
        $this->user_manager    = UserManager::instance();
        $this->project_manager = ProjectManager::instance();
        $this->event_manager   = EventManager::instance();

        $this->query_parser = new QueryParameterParser(
            new JsonDecoder()
        );
    }

    /**
     * @url OPTIONS {id}/labeled_items
     *
     * @param int $id Id of the project
     */
    public function optionsLabeledItems($id)
    {
        $this->sendAllowHeadersForLabeledItems();
    }

    /**
     * Get Labeled Items
     *
     * /!\ This route is under construction
     *
     * Get labeled items of this project.
     *
     * <p><b>query</b> is a json object with:</p>
     * <p>an array "labels_id" to search on all the given labels.
     *       When more than one label's id is provided, an AND operator is applied. <br>
     *       For example: { "labels_id": [ 10, 27] } will show all items that
     *       have both label 10 AND label 27. <br>
     *       Example: <pre>{"labels_id": [10,27]}</pre>
     * </p>
     *
     * @url GET {id}/labeled_items
     * @access hybrid
     *
     * @param int $id   Id of the project
     * @param string $query Search string in json format {@required}
     * @param int $limit    Number of elements displayed per page {@from path} {@max 50}
     * @param int $offset   Position of the first element to display {@from path} {@min 0}
     *
     * @return CollectionOfLabeledItemsRepresentation {@type CollectionOfLabeledItemsRepresentation}
     *
     * @throws RestException 406
     */
    public function getLabeledItems($id, $query, $limit = 50, $offset = 0)
    {
        $this->checkLimitValueIsAcceptable($limit);

        $project   = $this->getProjectForUser($id);
        $user      = $this->user_manager->getCurrentUser();
        $label_ids = $this->getLabelIdsFromQuery($query);

        $collection = new LabeledItemCollection($project, $user, $label_ids, $limit, $offset);
        $this->event_manager->processEvent($collection);

        $labeled_items = new CollectionOfLabeledItemsRepresentation();
        $labeled_items->build($collection);

        $this->sendAllowHeadersForLabeledItems();
        Header::sendPaginationHeaders($limit, $offset, $collection->getTotalSize(), self::MAX_LIMIT);

        return $labeled_items;
    }

    /**
     * @param $query
     * @return int[]
     */
    private function getLabelIdsFromQuery($query)
    {
        try {
            $label_ids = $this->query_parser->getArrayOfInt($query, 'labels_id');
        } catch (QueryParameterException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        if (count($label_ids) === 0) {
            throw new RestException(400, "labels_id must not be empty");
        }

        return $label_ids;
    }

    private function sendAllowHeadersForLabeledItems()
    {
        Header::allowOptionsGet();
    }

    private function checkLimitValueIsAcceptable($limit)
    {
        if (! $this->limitValueIsAcceptable($limit)) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }
    }

    private function limitValueIsAcceptable($limit)
    {
        return $limit <= self::MAX_LIMIT;
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return Project
     */
    private function getProjectForUser($id)
    {
        $project = $this->project_manager->getProject($id);
        $user    = $this->user_manager->getCurrentUser();

        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());
        return $project;
    }
}
