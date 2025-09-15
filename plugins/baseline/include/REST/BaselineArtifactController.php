<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use Luracast\Restler\RestException;
use Psr\Log\LoggerInterface;
use Tuleap\Baseline\Domain\BaselineArtifactNotFoundException;
use Tuleap\Baseline\Domain\BaselineArtifactService;
use Tuleap\Baseline\Domain\BaselineRepository;
use Tuleap\Baseline\Domain\BaselineRootArtifactNotFoundException;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Domain\NotAuthorizedException;
use Tuleap\Baseline\REST\Exception\ForbiddenRestException;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;

class BaselineArtifactController
{
    public const int MAX_ARTIFACTS_COUNT = 100;

    /** @var BaselineRepository */
    private $baseline_repository;

    /** @var BaselineArtifactService */
    private $baseline_artifact_service;

    /** @var CurrentUserProvider */
    private $current_user_provider;

    /** @var QueryParameterParser */
    private $query_parser;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        BaselineRepository $baseline_repository,
        BaselineArtifactService $baseline_artifact_service,
        CurrentUserProvider $current_user_provider,
        QueryParameterParser $query_parser,
        LoggerInterface $logger,
    ) {
        $this->baseline_repository       = $baseline_repository;
        $this->baseline_artifact_service = $baseline_artifact_service;
        $this->current_user_provider     = $current_user_provider;
        $this->query_parser              = $query_parser;
        $this->logger                    = $logger;
    }

    /**
     * @throws ForbiddenRestException
     * @throws NotFoundRestException
     * @throws RestException 520
     */
    public function get(int $baseline_id, ?string $query): BaselineArtifactCollectionRepresentation
    {
        $current_user = $this->current_user_provider->getUser();

        try {
            $baseline = $this->baseline_repository->findById($current_user, $baseline_id);
            if ($baseline === null) {
                throw new NotFoundRestException(
                    sprintf(
                        dgettext('tuleap-baseline', 'No baseline found with id %u'),
                        $baseline_id
                    )
                );
            }
            if ($query === null) {
                $artifacts = $this->baseline_artifact_service->findFirstLevelByBaseline($current_user, $baseline);
            } else {
                try {
                    $artifact_ids = $this->query_parser->getArrayOfInt($query, 'ids');
                } catch (QueryParameterException $ex) {
                    throw new RestException(400, $ex->getMessage());
                }
                if (count($artifact_ids) > self::MAX_ARTIFACTS_COUNT) {
                    throw new ForbiddenRestException(
                        sprintf(
                            dgettext('tuleap-baseline', 'No more than %u artifacts can be requested at once.'),
                            self::MAX_ARTIFACTS_COUNT
                        )
                    );
                }
                $artifacts = $this->baseline_artifact_service->findByBaselineAndIds(
                    $current_user,
                    $baseline,
                    $artifact_ids
                );
            }
            return BaselineArtifactCollectionRepresentation::fromArtifacts($artifacts);
        } catch (BaselineRootArtifactNotFoundException $exception) {
            $this->logger->error('Cannot get baseline artifacts', ['exception' => $exception]);
            throw new RestException(520);
        } catch (BaselineArtifactNotFoundException $exception) {
            throw new NotFoundRestException($exception->getMessage());
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
