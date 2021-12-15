<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\ProgramManagement\Adapter\ArtifactVisibleVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration\IterationContentDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration\IterationsDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\CrossReferenceRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\TitleValueRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\URIRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\UserStory\IsOpenRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Feature\BackgroundColorRetriever;
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxesDao;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\ArtifactFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerFactoryAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerOfArtifactRetriever;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\Content\IterationContentSearcher;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationNotFoundException;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;

final class IterationResource
{
    private const MAX_LIMIT = 50;
    public const  ROUTE     = 'iteration';

    private \UserManager $user_manager;
    private UserManagerAdapter $user_adapter;

    public function __construct()
    {
        $this->user_manager = \UserManager::instance();
        $this->user_adapter = new UserManagerAdapter($this->user_manager);
    }
    /**
     * Get the user stories linked to an iteration in team projects
     *
     *
     * @url GET {id}/content
     * @access hybrid
     *
     * @param int $id Id of the iteration
     * @param int $limit Number of elements displayed per page {@min 0} {@max 50}
     * @param int $offset Position of the first element to display {@min 0}
     *
     * @return UserStoryRepresentation[]
     *
     * @throws RestException 401
     * @throws RestException 400
     */
    public function getIterations(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $tracker_retriever   = new TrackerFactoryAdapter(\TrackerFactory::instance());
        $artifact_factory    = \Tracker_ArtifactFactory::instance();
        $artifact_retriever  = new ArtifactFactoryAdapter($artifact_factory);
        $visibility_verifier = new ArtifactVisibleVerifier($artifact_factory, $this->user_adapter);
        $iteration_retriever = new IterationContentSearcher(
            new IterationsDAO(),
            $visibility_verifier,
            new IterationContentDAO(),
            $visibility_verifier,
            new TitleValueRetriever($artifact_retriever),
            new URIRetriever($artifact_retriever),
            new CrossReferenceRetriever($artifact_retriever),
            new IsOpenRetriever($artifact_retriever),
            new BackgroundColorRetriever(
                new BackgroundColorBuilder(new BindDecoratorRetriever()),
                $artifact_retriever,
                $this->user_adapter
            ),
            new TrackerOfArtifactRetriever($artifact_retriever),
            new MirroredTimeboxesDao(),
        );

        $user = $this->user_manager->getCurrentUser();

        try {
            $user_identifier      = UserProxy::buildFromPFUser($user);
            $planned_user_stories = $iteration_retriever->retrievePlannedUserStories($id, $user_identifier);

            $representations = [];
            foreach ($planned_user_stories as $user_story) {
                $representations[] = UserStoryRepresentation::build($tracker_retriever, $user_story);
            }

            Header::sendPaginationHeaders($limit, $offset, count($representations), self::MAX_LIMIT);

            return array_slice($representations, $offset, $limit);
        } catch (IterationNotFoundException $e) {
            throw new I18NRestException(404, $e->getI18NExceptionMessage());
        }
    }

    /**
     * @url OPTIONS {id}/content
     *
     * @param int $id Id of the iteration
     */
    public function optionsContent(int $id): void
    {
        Header::allowOptionsGet();
    }
}
