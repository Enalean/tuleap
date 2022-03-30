<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\REST;

use Tuleap\Baseline\Domain\BaselineService;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Domain\NotAuthorizedException;
use Tuleap\Baseline\Domain\ProjectRepository;
use Tuleap\Baseline\REST\Exception\ForbiddenRestException;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;

class ProjectBaselineController
{
    /** @var CurrentUserProvider */
    private $current_user_provider;

    /** @var BaselineService */
    private $baseline_service;

    /** @var ProjectRepository */
    private $project_repository;

    public function __construct(
        CurrentUserProvider $current_user_provider,
        BaselineService $baseline_service,
        ProjectRepository $project_repository,
    ) {
        $this->current_user_provider = $current_user_provider;
        $this->baseline_service      = $baseline_service;
        $this->project_repository    = $project_repository;
    }

    /**
     * @return BaselinesPageRepresentation requested baseline page, excluding not authorized baselines. More over, page
     * total count is the real total count without any security filtering. Baselines are sorted by snapshot date (most recent first).
     * @throws NotFoundRestException 404
     * @throws ForbiddenRestException 403
     */
    public function get(int $project_id, int $limit, int $offset): BaselinesPageRepresentation
    {
        $current_user = $this->current_user_provider->getUser();

        $project = $this->project_repository->findById($current_user, $project_id);
        if ($project === null) {
            throw new NotFoundRestException(
                dgettext('tuleap-baseline', 'No project found with this id')
            );
        }
        try {
            $page = $this->baseline_service->findByProject(
                $current_user,
                $project,
                $limit,
                $offset
            );
            return BaselinesPageRepresentation::build($page);
        } catch (NotAuthorizedException $exception) {
            throw new ForbiddenRestException(
                sprintf(
                    dgettext('tuleap-baseline', 'This operation is not allowed. %s'),
                    $exception->getMessage()
                )
            );
        }
    }
}
