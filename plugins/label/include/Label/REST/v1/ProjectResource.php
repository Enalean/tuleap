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
use Tuleap\Label\Exceptions\DuplicatedParameterValueException;
use Tuleap\Label\Exceptions\EmptyParameterException;
use Tuleap\Label\Exceptions\InvalidParameterTypeException;
use Tuleap\Label\Exceptions\MissingMandatoryParameterException;
use Tuleap\Label\LabeledItemCollection;
use Tuleap\Label\LabeledItemQueryParser;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\ProjectAuthorization;
use URLVerification;
use UserManager;

class ProjectResource
{
    const MAX_LIMIT = 50;

    /** @var UserManager */
    private $user_manager;

    /** @var EventManager */
    private $event_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var LabeledItemQueryParser */
    private $labeled_items_query_parser;

    public function __construct()
    {
        $this->user_manager    = UserManager::instance();
        $this->project_manager = ProjectManager::instance();
        $this->event_manager   = EventManager::instance();

        $this->labeled_items_query_parser = new LabeledItemQueryParser(
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
     * @throws 406
     */
    public function getLabeledItems($id, $query, $limit = 50, $offset = 0)
    {
        $this->checkLimitValueIsAcceptable($limit);

        $project = $this->getProjectForUser($id);
        $user    = $this->user_manager->getCurrentUser();
        try {
            $label_ids = $this->labeled_items_query_parser->getLabelIdsFromRoute($query);
            $event     = new LabeledItemCollection($project, $user, $label_ids, $limit, $offset);
            $this->event_manager->processEvent($event);

            $labeled_items = new CollectionOfLabeledItemsRepresentation();
            $labeled_items->build($event);

            $this->sendAllowHeadersForLabeledItems();
            Header::sendPaginationHeaders($limit, $offset, $event->getTotalSize(), self::MAX_LIMIT);
        } catch (MissingMandatoryParameterException $e) {
            throw new RestException(400, "Missing labels_id entry in the query parameter");
        } catch (InvalidParameterTypeException $e) {
            throw new RestException(400, "labels_id must be an array of int");
        } catch (EmptyParameterException $e) {
            throw new RestException(400, "labels_id must not be empty");
        } catch (DuplicatedParameterValueException $e) {
            throw new RestException(400, 'One label or more are duplicated: ' . implode(',', $e->getDuplicates()));
        }

        return $labeled_items;
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
     * @throws 403
     * @throws 404
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
