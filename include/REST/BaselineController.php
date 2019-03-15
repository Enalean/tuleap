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

use Tuleap\Baseline\BaselineArtifactRepository;
use Tuleap\Baseline\BaselineService;
use Tuleap\Baseline\CurrentUserProvider;
use Tuleap\Baseline\NotAuthorizedException;
use Tuleap\Baseline\TransientBaseline;
use Tuleap\REST\I18NRestException;

class BaselineController
{
    /** @var CurrentUserProvider */
    private $current_user_provider;

    /** @var BaselineService */
    private $baseline_service;

    /** @var BaselineArtifactRepository */
    private $baseline_artifact_repository;

    public function __construct(
        CurrentUserProvider $current_user_provider,
        BaselineService $baseline_service,
        BaselineArtifactRepository $baseline_artifact_repository
    ) {
        $this->current_user_provider        = $current_user_provider;
        $this->baseline_service             = $baseline_service;
        $this->baseline_artifact_repository = $baseline_artifact_repository;
    }

    /**
     * @throws I18NRestException 404
     * @throws I18NRestException 403
     * @throws \Luracast\Restler\RestException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     */
    public function post(string $name, int $milestone_id): BaselineRepresentation
    {
        $current_user = $this->current_user_provider->getUser();
        $milestone    = $this->baseline_artifact_repository->findById($current_user, $milestone_id);
        if ($milestone === null) {
            throw new I18NRestException(
                404,
                sprintf(
                    dgettext('tuleap-baseline', 'No milestone found with id %u'),
                    $milestone_id
                )
            );
        }

        $baseline = new TransientBaseline($name, $milestone);
        try {
            $created_baseline = $this->baseline_service->create($current_user, $baseline);
            return BaselineRepresentation::fromBaseline($created_baseline);
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

    /**
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     */
    public function getById(int $id): BaselineRepresentation
    {
        try {
            $current_user = $this->current_user_provider->getUser();
            $baseline     = $this->baseline_service->findById($current_user, $id);
            if ($baseline === null) {
                throw new I18NRestException(
                    404,
                    sprintf(
                        dgettext('tuleap-baseline', 'No baseline found with id %u'),
                        $id
                    )
                );
            }
            return BaselineRepresentation::fromBaseline($baseline);
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
