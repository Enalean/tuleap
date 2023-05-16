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

use Luracast\Restler\RestException;
use Psr\Log\LoggerInterface;
use Tuleap\REST\Header;
use Tuleap\Roadmap\FilterReportDao;
use Tuleap\Roadmap\NatureForRoadmapDao;
use Tuleap\Roadmap\ReportToFilterArtifactsRetriever;
use Tuleap\Roadmap\RoadmapWidgetDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Semantic\Progress\MethodBuilder;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressDao;
use Tuleap\Tracker\Semantic\Status\SemanticStatusRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class RoadmapResource
{
    public const MAX_LIMIT = 100;

    /**
     * @url OPTIONS {id}/tasks
     *
     * @param int $id Id of the roadmap
     */
    public function optionsTasks(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get the tasks
     *
     * Retrieve paginated tasks of a given roadmap
     *
     * <pre>
     * /!\ Roadmap REST route is under construction and subject to changes /!\
     * </pre>
     *
     * @url GET {id}/tasks
     * @access hybrid
     *
     * @param int $id     Id of the roadmap
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
    public function getTasks(int $id, int $offset = 0, int $limit = self::MAX_LIMIT): array
    {
        $this->optionsTasks($id);

        $tracker_artifact_factory   = \Tracker_ArtifactFactory::instance();
        $form_element_factory       = \Tracker_FormElementFactory::instance();
        $tracker_factory            = \TrackerFactory::instance();
        $semantic_timeframe_builder = SemanticTimeframeBuilder::build();

        $progress_dao = new SemanticProgressDao();
        $retriever    = new RoadmapTasksRetriever(
            new RoadmapWidgetDao(),
            \ProjectManager::instance(),
            \UserManager::instance(),
            new \URLVerification(),
            $tracker_factory,
            $semantic_timeframe_builder,
            $tracker_artifact_factory,
            new DependenciesRetriever(new NatureForRoadmapDao()),
            new RoadmapTasksOutOfDateFilter(
                new TaskOutOfDateDetector(
                    new SemanticStatusRetriever(),
                    $semantic_timeframe_builder,
                    $this->getLogger(),
                ),
            ),
            new SemanticProgressBuilder(
                $progress_dao,
                new MethodBuilder(
                    $form_element_factory,
                    $progress_dao,
                    new TypePresenterFactory(
                        new TypeDao(),
                        new ArtifactLinksUsageDao()
                    )
                )
            ),
            \BackendLogger::getDefaultLogger(),
            new ReportToFilterArtifactsRetriever(new FilterReportDao(), \Tracker_ReportFactory::instance()),
        );

        $tasks = $retriever->getTasks($id, $limit, $offset);

        Header::sendPaginationHeaders($limit, $offset, $tasks->getTotalSize(), self::MAX_LIMIT);

        return $tasks->getRepresentations();
    }

    /**
     * @url OPTIONS {id}/iterations
     *
     * @param int $id Id of the roadmap
     */
    public function optionsIterations(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get the iterations
     *
     * Retrieve paginated iterations of a given roadmap
     *
     * <pre>
     * /!\ Roadmap REST route is under construction and subject to changes /!\
     * </pre>
     *
     * @url    GET {id}/iterations
     * @access hybrid
     *
     * @param int $id     Id of the roadmap
     * @param int $level  Level of the iteration {@min 1} {@max 2}
     * @param int $offset Position of the first element to display{ @min 0}
     * @param int $limit  Number of elements displayed per page {@min 0} {@max 100}
     *
     * @return array {@type IterationRepresentation}
     * @psalm-return IterationRepresentation[]
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getIterations(
        int $id,
        int $level,
        int $offset = 0,
        int $limit = self::MAX_LIMIT,
    ): array {
        $this->optionsIterations($id);

        $tracker_artifact_factory   = \Tracker_ArtifactFactory::instance();
        $semantic_timeframe_builder = SemanticTimeframeBuilder::build();

        $retriever = new IterationsRetriever(
            new RoadmapWidgetDao(),
            \ProjectManager::instance(),
            \UserManager::instance(),
            new \URLVerification(),
            \TrackerFactory::instance(),
            $semantic_timeframe_builder,
            $tracker_artifact_factory,
            \BackendLogger::getDefaultLogger()
        );

        $iterations = $retriever->getIterations($id, $level, $limit, $offset);

        Header::sendPaginationHeaders($limit, $offset, $iterations->getTotalSize(), self::MAX_LIMIT);

        return $iterations->getRepresentations();
    }

    private function getLogger(): LoggerInterface
    {
        return \BackendLogger::getDefaultLogger();
    }
}
