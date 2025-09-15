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

use DateTime;
use DateTimeImmutable;
use Exception;
use Tuleap\Baseline\Domain\BaselineArtifactRepository;
use Tuleap\Baseline\Domain\BaselineDeletionException;
use Tuleap\Baseline\Domain\BaselineService;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Domain\NotAuthorizedException;
use Tuleap\Baseline\REST\Exception\ForbiddenRestException;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\Baseline\Domain\TransientBaseline;
use Tuleap\REST\I18NRestException;
use Tuleap\User\Password\PasswordExpiredException;

class BaselineController
{
    public const string DATE_TIME_FORMAT = DateTime::ATOM;

    /** @var CurrentUserProvider */
    private $current_user_provider;

    /** @var BaselineService */
    private $baseline_service;

    /** @var BaselineArtifactRepository */
    private $baseline_artifact_repository;

    public function __construct(
        CurrentUserProvider $current_user_provider,
        BaselineService $baseline_service,
        BaselineArtifactRepository $baseline_artifact_repository,
    ) {
        $this->current_user_provider        = $current_user_provider;
        $this->baseline_service             = $baseline_service;
        $this->baseline_artifact_repository = $baseline_artifact_repository;
    }

    /**
     * @throws NotFoundRestException 404
     * @throws ForbiddenRestException 403
     * @throws \Luracast\Restler\RestException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     */
    public function post(string $name, int $artifact_id, ?string $snapshot_date_as_string): BaselineRepresentation
    {
        $current_user = $this->current_user_provider->getUser();
        $artifact     = $this->baseline_artifact_repository->findById($current_user, $artifact_id);
        if ($artifact === null) {
            throw new NotFoundRestException(
                sprintf(
                    dgettext('tuleap-baseline', 'No artifact found with id %u'),
                    $artifact_id
                )
            );
        }

        $snapshot_date = null;
        if ($snapshot_date_as_string !== null) {
            $snapshot_date = DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, $snapshot_date_as_string);
            if (! $snapshot_date) {
                throw new I18NRestException(
                    400,
                    sprintf(
                        dgettext('tuleap-baseline', 'Bad snapshot date format: %s. Expected: %s'),
                        $snapshot_date_as_string,
                        self::DATE_TIME_FORMAT
                    )
                );
            }
        }
        $baseline = new TransientBaseline($name, $artifact, $snapshot_date);
        try {
            $created_baseline = $this->baseline_service->create($current_user, $baseline);
            return BaselineRepresentation::fromBaseline($created_baseline);
        } catch (NotAuthorizedException $exception) {
            $this->throw403Exception($exception);
        }
    }

    /**
     * @throws NotFoundRestException
     * @throws ForbiddenRestException
     * @throws I18NRestException 409
     */
    public function delete(int $id): void
    {
        $current_user = $this->current_user_provider->getUser();
        $baseline     = $this->baseline_service->findById($current_user, $id);
        if ($baseline === null) {
            $this->throw404Exception($id);
        }

        try {
            $this->baseline_service->delete($current_user, $baseline);
        } catch (NotAuthorizedException $exception) {
            $this->throw403Exception($exception);
        } catch (BaselineDeletionException $exception) {
            $this->throw409Exception($exception);
        }
    }

    /**
     * @throws NotFoundRestException 404
     */
    public function getById(int $id): BaselineRepresentation
    {
        $current_user = $this->current_user_provider->getUser();
        $baseline     = $this->baseline_service->findById($current_user, $id);
        if ($baseline === null) {
            $this->throw404Exception($id);
        }
        return BaselineRepresentation::fromBaseline($baseline);
    }

    /**
     * @throws NotFoundRestException 404
     */
    private function throw404Exception($id): void
    {
        $message = sprintf(
            dgettext('tuleap-baseline', 'No baseline found with id %u'),
            $id
        );
        throw new NotFoundRestException(
            $message
        );
    }

    /**
     * @throws ForbiddenRestException 403
     */
    private function throw403Exception($exception): void
    {
        throw new ForbiddenRestException(
            sprintf(
                dgettext('tuleap-baseline', 'This operation is not allowed. %s'),
                $exception->getMessage()
            )
        );
    }

    /**
     * @throws I18NRestException 409
     */
    private function throw409Exception(Exception $exception): void
    {
        throw new I18NRestException(
            409,
            sprintf(
                dgettext('tuleap-baseline', 'This operation is possible for now. %s'),
                $exception->getMessage()
            )
        );
    }
}
