<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\CrossTracker\REST\v1;

use Luracast\Restler\RestException;
use ProjectManager;
use Tuleap\CrossTracker\Report\Query\CrossTrackerQueryDao;
use Tuleap\CrossTracker\Report\Query\CrossTrackerQueryFactory;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerQueryRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerWidgetRepresentation;
use Tuleap\CrossTracker\Widget\CrossTrackerWidgetDao;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\User\ProvideCurrentUser;
use URLVerification;
use UserManager;

final class CrossTrackerWidgetResource extends AuthenticatedResource
{
    public const  ROUTE     = 'crosstracker_widget';
    public const  MAX_LIMIT = 50;

    private readonly ProvideCurrentUser $current_user_provider;

    public function __construct()
    {
        $this->current_user_provider = UserManager::instance();
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id ID of the widget {@from path}
     */
    public function optionsId(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get a CrossTracker widget
     *
     * It returns the queries belonging to the widget
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id ID of the widget {@from path}
     *
     * @throws RestException 401
     * @throws RestException 404
     */
    public function getId(int $id): CrossTrackerWidgetRepresentation
    {
        $this->checkAccess();
        Header::allowOptionsGet();

        try {
            if (! $this->getWidgetDao()->searchWidgetExistence($id)) {
                throw new CrossTrackerWidgetNotFoundException();
            }

            $factory      = new CrossTrackerQueryFactory($this->getQueryDao());
            $queries      = $factory->getByWidgetId($id);
            $current_user = $this->current_user_provider->getCurrentUser();
            $this->getUserIsAllowedToSeeWidgetChecker()->checkUserIsAllowedToSeeWidget($current_user, $id);

            $representations = [];
            foreach ($queries as $query) {
                $representations[] = CrossTrackerQueryRepresentation::fromQuery($query);
            }

            return new CrossTrackerWidgetRepresentation($representations);
        } catch (CrossTrackerWidgetNotFoundException) {
            throw new I18NRestException(404, sprintf(dgettext('tuleap-crosstracker', 'Widget with id %d not found'), $id));
        }
    }

    private function getWidgetDao(): CrossTrackerWidgetDao
    {
        return new CrossTrackerWidgetDao();
    }

    private function getQueryDao(): CrossTrackerQueryDao
    {
        return new CrossTrackerQueryDao();
    }

    private function getUserIsAllowedToSeeWidgetChecker(): UserIsAllowedToSeeWidgetChecker
    {
        return new UserIsAllowedToSeeWidgetChecker(
            $this->getWidgetDao(),
            ProjectManager::instance(),
            new URLVerification(),
        );
    }
}
