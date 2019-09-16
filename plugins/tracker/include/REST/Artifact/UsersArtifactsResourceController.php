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

namespace Tuleap\Tracker\REST\Artifact;

use Luracast\Restler\RestException;
use Tracker_ArtifactFactory;
use Tuleap\REST\InvalidParameterTypeException;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\MissingMandatoryParameterException;
use Tuleap\REST\QueryParameterParser;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\User\REST\v1\UserResource;
use UserManager;

class UsersArtifactsResourceController
{
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(UserManager $user_manager, Tracker_ArtifactFactory $artifact_factory)
    {
        $this->user_manager     = $user_manager;
        $this->artifact_factory = $artifact_factory;
    }

    /**
     * @throws RestException
     */
    public function getArtifacts(string $id, string $query, int $offset = 0, int $limit = 250): array
    {
        if ($id !== UserResource::SELF_ID) {
            throw new RestException(403, 'This route only support `self` pseudo id');
        }

        $query_parameter_parser = new QueryParameterParser(new JsonDecoder());
        try {
            $submitted_by = $query_parameter_parser->getBoolean($query, 'submitted_by');
        } catch (MissingMandatoryParameterException $exception) {
            $submitted_by = false;
        } catch (InvalidParameterTypeException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        try {
            $assigned_to = $query_parameter_parser->getBoolean($query, 'assigned_to');
        } catch (MissingMandatoryParameterException $exception) {
            $assigned_to = false;
        } catch (InvalidParameterTypeException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        $user = $this->user_manager->getCurrentUser();
        if ($submitted_by && $assigned_to) {
            $users_artifacts = $this->artifact_factory->getUserOpenArtifactsSubmittedByOrAssignedTo($user, $offset, $limit);
        } elseif ($submitted_by) {
            $users_artifacts = $this->artifact_factory->getUserOpenArtifactsSubmittedBy($user, $offset, $limit);
        } elseif ($assigned_to) {
            $users_artifacts = $this->artifact_factory->getUserOpenArtifactsAssignedTo($user, $offset, $limit);
        } else {
            throw new RestException(400, 'You must specify either `assigned_to: true` or `submitted_by: true`');
        }

        $artifacts = [];
        foreach ($users_artifacts->getArtifacts() as $artifact) {
            assert($artifact instanceof \Tracker_Artifact);
            $artifacts[] = (new MyArtifactsRepresentation())->build(
                $artifact,
                (new MinimalTrackerRepresentation())->build($artifact->getTracker())
            );
        }

        return [$users_artifacts->getTotalNumberOfArtifacts(), $artifacts];
    }
}
