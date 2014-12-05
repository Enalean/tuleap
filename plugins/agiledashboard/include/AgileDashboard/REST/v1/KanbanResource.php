<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\REST\Header;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanDao;
use AgileDashboard_KanbanNotFoundException;
use AgileDashboard_KanbanCannotAccessException;
use UserManager;
use TrackerFactory;
use PFUser;

class KanbanResource {

    const MAX_LIMIT = 100;

    /** @var AgileDashboard_KanbanFactory */
    private $kanban_factory;

    public function __construct() {
        $this->kanban_factory = new AgileDashboard_KanbanFactory(
            TrackerFactory::instance(),
            new AgileDashboard_KanbanDao()
        );
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptions();
    }

    /**
     * Get kanban
     *
     * Get the definition of a given kanban
     *
     * @url GET {id}
     *
     * @param int $id Id of the kanban
     *
     * @return Tuleap\AgileDashboard\REST\v1\KanbanRepresentation
     *
     * @throws 403
     * @throws 404
     */
    protected function getId($id) {
        $user   = $this->getCurrentUser();
        $kanban = $this->getKanban($user, $id);

        $kanban_representation = new KanbanRepresentation();
        $kanban_representation->build($kanban);

        Header::allowOptionsGet();
        return $kanban_representation;
    }

    /**
     * Return info about milestone if exists
     *
     * @url OPTIONS {id}
     *
     * @param string $id Id of the milestone
     */
    public function optionsId($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get backlog
     *
     * Get the backlog of a given kanban
     *
     * @url GET {id}/backlog
     *
     * @param int $id Id of the kanban
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @return Tuleap\AgileDashboard\REST\v1\KanbanBacklogRepresentation
     *
     * @throws 403
     * @throws 404
     */
    protected function getBacklog($id, $limit = 10, $offset = 0) {
        $user   = $this->getCurrentUser();
        $kanban = $this->getKanban($user, $id);

        $backlog_representation = new KanbanBacklogRepresentation();
        $backlog_representation->build($user, $kanban, $limit, $offset);

        Header::allowOptionsGet();
        Header::sendPaginationHeaders($limit, $offset, $backlog_representation->total_size, self::MAX_LIMIT);

        return $backlog_representation;
    }

    /**
     * Return info about milestone if exists
     *
     * @url OPTIONS {id}/backlog
     *
     * @param string $id Id of the milestone
     */
    public function optionsBacklog($id) {
        Header::allowOptionsGet();
    }

    /** @return AgileDashboard_Kanban */
    private function getKanban(PFUser $user, $id) {
        try {
            $kanban = $this->kanban_factory->getKanban($user, $id);
        } catch (AgileDashboard_KanbanNotFoundException $exception) {
            throw new RestException(404);
        } catch (AgileDashboard_KanbanCannotAccessException $exception) {
            throw new RestException(403);
        }

        return $kanban;
    }

    private function getCurrentUser() {
        $user = UserManager::instance()->getCurrentUser();
        if (! $user->useLabFeatures()) {
            throw new RestException(403, 'You must activate lab features');
        }

        return $user;
    }
}
