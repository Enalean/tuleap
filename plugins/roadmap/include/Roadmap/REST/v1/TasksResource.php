<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use EventManager;
use Luracast\Restler\RestException;
use Tuleap\REST\Header;
use Tuleap\Roadmap\NatureForRoadmapDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\SystemTypePresenterBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Semantic\Progress\MethodBuilder;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressDao;
use Tuleap\Tracker\Semantic\Status\CachedSemanticStatusRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use UserManager;

final class TasksResource
{
    public const MAX_LIMIT = 100;
    public const ROUTE     = 'roadmap_tasks';

    /**
     * @url OPTIONS {id}/subtasks
     *
     * @param int $id Id of the roadmap
     */
    public function optionsSubtasks(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get the subtasks
     *
     * Retrieve paginated subtasks of a given roadmap task
     *
     * <pre>
     * /!\ Roadmap REST route is under construction and subject to changes /!\
     * </pre>
     *
     * @url    GET {id}/subtasks
     * @access hybrid
     *
     * @param int $id     Id of the task
     * @param int $offset Position of the first element to display{ @min 0}
     * @param int $limit  Number of elements displayed per page {@min 0} {@max 100}
     *
     * @return array {@type TaskRepresentation}
     * @psalm-return TaskRepresentation[]
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getSubtasks(int $id, int $offset = 0, int $limit = self::MAX_LIMIT): array
    {
        $this->optionsSubtasks($id);

        $form_element_factory       = \Tracker_FormElementFactory::instance();
        $semantic_timeframe_builder = SemanticTimeframeBuilder::build();
        $semantic_status_retriever  = CachedSemanticStatusRetriever::instance();

        $progress_dao = new SemanticProgressDao();
        $logger       = \BackendLogger::getDefaultLogger();
        $retriever    = new SubtasksRetriever(
            \Tracker_ArtifactFactory::instance(),
            UserManager::instance(),
            new TaskRepresentationBuilderForTrackerCache(
                $semantic_timeframe_builder,
                new DependenciesRetriever(new NatureForRoadmapDao()),
                new SemanticProgressBuilder(
                    $progress_dao,
                    new MethodBuilder(
                        $form_element_factory,
                        $progress_dao,
                        new TypePresenterFactory(
                            new TypeDao(),
                            new ArtifactLinksUsageDao(),
                            new SystemTypePresenterBuilder(EventManager::instance())
                        )
                    )
                ),
                $semantic_status_retriever,
                $logger
            ),
            new TaskOutOfDateDetector(
                $semantic_status_retriever,
                $semantic_timeframe_builder,
                $logger,
            ),
            $logger,
        );

        $tasks = $retriever->getTasks($id, $limit, $offset);

        Header::sendPaginationHeaders($limit, $offset, $tasks->getTotalSize(), self::MAX_LIMIT);

        return $tasks->getRepresentations();
    }
}
