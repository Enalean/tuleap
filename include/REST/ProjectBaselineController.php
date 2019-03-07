<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

use Tuleap\Baseline\BaselineService;
use Tuleap\Baseline\NotAuthorizedException;
use Tuleap\Baseline\ProjectRepository;
use Tuleap\REST\I18NRestException;

class ProjectBaselineController
{
    /**
     * @var BaselineService
     */
    private $baseline_service;

    /** @var ProjectRepository */
    private $project_repository;

    public function __construct(BaselineService $baseline_service, ProjectRepository $project_repository)
    {
        $this->baseline_service   = $baseline_service;
        $this->project_repository = $project_repository;
    }

    /**
     * @throws I18NRestException 404
     * @throws I18NRestException 403
     */
    public function get(int $project_id, int $limit, int $offset): BaselinesPageRepresentation
    {
        $project = $this->project_repository->findById($project_id);
        if ($project === null) {
            throw new I18NRestException(
                404,
                dgettext('tuleap-baseline', 'No project found with this id')
            );
        }
        try {
            $page = $this->baseline_service->findByProject(
                $project,
                $limit,
                $offset
            );
            return BaselinesPageRepresentation::build($page);
        } catch (NotAuthorizedException $exception) {
            throw new I18NRestException(
                403,
                sprintf(
                    dgettext('tuleap-baseline', 'This operation is not allowed. %s'),
                    $exception->getMessage()
                )
            );
        }
    }
}
