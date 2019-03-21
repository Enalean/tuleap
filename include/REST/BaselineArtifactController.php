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

use Luracast\Restler\RestException;
use Tuleap\Baseline\BaselineArtifactNotFoundException;
use Tuleap\Baseline\BaselineArtifactService;
use Tuleap\Baseline\BaselineRootArtifactNotFoundException;
use Tuleap\Baseline\BaselineService;
use Tuleap\Baseline\CurrentUserProvider;
use Tuleap\Baseline\NotAuthorizedException;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\QueryParameterException;
use Tuleap\REST\QueryParameterParser;
use Tuleap\REST\RESTLogger;

class BaselineArtifactController
{
    public const MAX_ARTIFACTS_COUNT = 100;

    /** @var BaselineService */
    private $baseline_service;

    /** @var BaselineArtifactService */
    private $baseline_artifact_service;

    /** @var CurrentUserProvider */
    private $current_user_provider;

    /** @var QueryParameterParser */
    private $query_parser;

    /** @var RESTLogger */
    private $logger;

    public function __construct(
        BaselineService $baseline_service,
        BaselineArtifactService $baseline_artifact_service,
        CurrentUserProvider $current_user_provider,
        QueryParameterParser $query_parser,
        RESTLogger $logger
    ) {
        $this->baseline_service          = $baseline_service;
        $this->baseline_artifact_service = $baseline_artifact_service;
        $this->current_user_provider     = $current_user_provider;
        $this->query_parser              = $query_parser;
        $this->logger                    = $logger;
    }

    /**
     * @return BaselineArtifactCollectionRepresentation
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     * @throws RestException 520
     */
    public function get(int $baseline_id, ?string $query): BaselineArtifactCollectionRepresentation
    {
        $current_user = $this->current_user_provider->getUser();

        try {
            $baseline = $this->baseline_service->findById($current_user, $baseline_id);
            if ($baseline === null) {
                throw new I18NRestException(
                    404,
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
                    throw new I18NRestException(
                        403,
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
            $this->logger->error('Cannot get baseline artifacts', $exception);
            throw new RestException(520);
        } catch (BaselineArtifactNotFoundException $exception) {
            throw new I18NRestException(404, $exception->getMessage());
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
