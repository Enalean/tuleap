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

use PFUser;
use Tuleap\Baseline\Domain\Baseline;
use Tuleap\Baseline\Domain\BaselineRepository;
use Tuleap\Baseline\Domain\ComparisonRepository;
use Tuleap\Baseline\Domain\ComparisonService;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Domain\InvalidComparisonException;
use Tuleap\Baseline\Domain\NotAuthorizedException;
use Tuleap\Baseline\REST\Exception\ForbiddenRestException;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\Baseline\Domain\TransientComparison;
use Tuleap\REST\I18NRestException;

class ComparisonController
{
    /** @var ComparisonService */
    private $comparison_service;

    /** @var CurrentUserProvider */
    private $current_user_provider;

    /** @var BaselineRepository */
    private $baseline_repository;

    /** @var ComparisonRepository */
    private $comparison_repository;

    public function __construct(
        ComparisonService $comparison_service,
        CurrentUserProvider $current_user_provider,
        BaselineRepository $baseline_repository,
        ComparisonRepository $comparison_repository,
    ) {
        $this->comparison_service    = $comparison_service;
        $this->current_user_provider = $current_user_provider;
        $this->baseline_repository   = $baseline_repository;
        $this->comparison_repository = $comparison_repository;
    }

    /**
     * @throws I18NRestException 400
     * @throws ForbiddenRestException
     * @throws NotFoundRestException
     */
    public function post(
        ?string $name,
        ?string $comment,
        int $base_baseline_id,
        int $compared_to_baseline_id,
    ): ComparisonRepresentation {
        $current_user = $this->current_user_provider->getUser();

        $base_baseline        = $this->findBaselineByIdOrThrow($current_user, $base_baseline_id);
        $compared_to_baseline = $this->findBaselineByIdOrThrow($current_user, $compared_to_baseline_id);

        try {
            $transient_comparison = new TransientComparison($name, $comment, $base_baseline, $compared_to_baseline);
            $comparison           = $this->comparison_service->create($transient_comparison, $current_user);
            return ComparisonRepresentation::fromComparison($comparison);
        } catch (InvalidComparisonException $exception) {
            throw new I18NRestException(400, $exception->getMessage());
        } catch (NotAuthorizedException $exception) {
            throw new ForbiddenRestException($exception->getMessage());
        }
    }

    /**
     * @throws NotFoundRestException
     */
    public function getById(int $id): ComparisonRepresentation
    {
        $current_user = $this->current_user_provider->getUser();
        $comparison   = $this->comparison_service->findById($current_user, $id);
        if ($comparison === null) {
            $this->throwNotFoundException($id);
        }
        return ComparisonRepresentation::fromComparison($comparison);
    }

    /**
     * @throws NotFoundRestException
     * @throws ForbiddenRestException
     */
    public function delete(int $id): void
    {
        $current_user = $this->current_user_provider->getUser();
        $comparison   = $this->comparison_repository->findById($current_user, $id);
        if ($comparison === null) {
            $this->throwNotFoundException($id);
        }

        try {
            $this->comparison_service->delete($current_user, $comparison);
        } catch (NotAuthorizedException $exception) {
            throw new ForbiddenRestException($exception->getMessage());
        }
    }

    /**
     * @throws NotFoundRestException
     */
    private function findBaselineByIdOrThrow(PFUser $current_user, int $baseline_id): Baseline
    {
        $base_baseline = $this->baseline_repository->findById($current_user, $baseline_id);
        if ($base_baseline === null) {
            throw new NotFoundRestException(
                sprintf(
                    dgettext('tuleap-baseline', 'No baseline found with id %u'),
                    $baseline_id
                )
            );
        }
        return $base_baseline;
    }

    /**
     * @throws NotFoundRestException
     */
    private function throwNotFoundException(int $id): void
    {
        throw new NotFoundRestException(
            sprintf(
                dgettext('tuleap-baseline', 'No comparison found with id %u'),
                $id
            )
        );
    }
}
