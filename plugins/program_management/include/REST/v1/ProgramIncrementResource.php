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

namespace Tuleap\ProgramManagement\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Rank\FeaturesRankOrderer;
use Tuleap\ProgramManagement\Adapter\Program\Feature\BackgroundColorRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\ContentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\FeatureContentRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\ProgramIncrementRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureDAO;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureRepresentationBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\UserStoryLinkedToFeatureChecker;
use Tuleap\ProgramManagement\Adapter\Program\Feature\VerifyIsVisibleFeatureAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Plan\CanPrioritizeFeaturesDAO;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanDao;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDao;
use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerException;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\RetrieveFeatureContent;
use Tuleap\ProgramManagement\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Content\ContentChange;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Content\ContentModifier;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Program\Plan\FeatureCannotBePlannedInProgramIncrementException;
use Tuleap\ProgramManagement\Program\Plan\InvalidFeatureIdInProgramIncrementException;
use Tuleap\ProgramManagement\Program\ProgramNotFoundException;
use Tuleap\ProgramManagement\Program\ProgramSearcher;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;

final class ProgramIncrementResource extends AuthenticatedResource
{
    private const MAX_LIMIT = 50;
    public const  ROUTE     = 'program_increment';

    /**
     * @var RetrieveFeatureContent
     */
    public $program_increment_content_retriever;
    /**
     * @var \UserManager
     */
    private $user_manager;

    public function __construct()
    {
        $this->user_manager                        = \UserManager::instance();
        $artifact_factory                          = \Tracker_ArtifactFactory::instance();
        $this->program_increment_content_retriever = new FeatureContentRetriever(
            new ProgramIncrementRetriever($artifact_factory, new ProgramIncrementsDAO()),
            new ContentDao(),
            new FeatureRepresentationBuilder(
                $artifact_factory,
                \Tracker_FormElementFactory::instance(),
                new BackgroundColorRetriever(new BackgroundColorBuilder(new BindDecoratorRetriever())),
                new VerifyIsVisibleFeatureAdapter($artifact_factory),
                new UserStoryLinkedToFeatureChecker(new ArtifactsLinkedToParentDao(), new PlanningAdapter(\PlanningFactory::build()), $artifact_factory)
            ),
            new ProgramSearcher(new ProgramDao())
        );
    }

    /**
     * Get content of a program increment
     *
     * In a program increment get all the elements planned in team and linked to a program increment
     *
     * @url GET {id}/content
     * @access hybrid
     *
     * @param int $id Id of the program
     * @param int $limit Number of elements displayed per page {@min 0} {@max 50}
     * @param int $offset Position of the first element to display {@min 0}
     *
     * @return FeatureRepresentation[]
     *
     * @throws RestException 401
     * @throws RestException 400
     */
    public function getBacklog(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $user = $this->user_manager->getCurrentUser();
        try {
            $elements = $this->program_increment_content_retriever->retrieveProgramIncrementContent($id, $user);

            Header::sendPaginationHeaders($limit, $offset, count($elements), self::MAX_LIMIT);

            return array_slice($elements, $offset, $limit);
        } catch (ProgramIncrementNotFoundException | ProgramNotFoundException $e) {
            throw new RestException(404, $e->getMessage());
        } catch (PlanTrackerException | ProgramTrackerException $e) {
            throw new RestException(404, $e->getMessage());
        }
    }

    /**
     * Change the program increment's contents
     *
     * Plan elements in the program increment.
     *
     * <pre>⚠ This route is under construction ⚠</pre>
     *
     * <br>
     * Add example
     * <pre>
     * {
     *   "add": [
     *     { "id": 34 }
     *   ],
     *   "order": { "ids": [ 34 ], "compared_to": 35, "direction": "before" }
     * }
     * </pre>
     * <br>
     * The feature with id 34 is planned (added to the contents) of the Program Increment. And it is added before feature with id 35.
     * <code>order</code> is not mandatory.
     *
     * @url    PATCH {id}/content
     * @access protected
     *
     * @param int                                        $id                   ID of the program increment
     * @param ProgramIncrementContentPatchRepresentation $patch_representation {@from body}
     * @throws RestException 403
     * @throws RestException 404
     */
    public function patchContent(int $id, ProgramIncrementContentPatchRepresentation $patch_representation): void
    {
        $user     = $this->user_manager->getCurrentUser();
        $modifier = new ContentModifier(
            new PrioritizeFeaturesPermissionVerifier(
                \ProjectManager::instance(),
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    \EventManager::instance()
                ),
                new CanPrioritizeFeaturesDAO()
            ),
            new ProgramIncrementRetriever(\Tracker_ArtifactFactory::instance(), new ProgramIncrementsDAO()),
            new ProgramSearcher(new ProgramDao()),
            new PlanDao(),
            new FeaturesRankOrderer(\Tracker_Artifact_PriorityManager::build()),
            new FeatureDAO()
        );
        try {
            $potential_feature_id_to_add = $patch_representation->add[0]->id ?? null;
            $modifier->modifyContent($user, $id, new ContentChange($potential_feature_id_to_add, $patch_representation->order));
        } catch (ProgramTrackerException | ProgramIncrementNotFoundException | ProgramNotFoundException $e) {
            throw new RestException(404, $e->getMessage());
        } catch (NotAllowedToPrioritizeException $e) {
            throw new RestException(403, $e->getMessage());
        } catch (FeatureCannotBePlannedInProgramIncrementException | InvalidFeatureIdInProgramIncrementException $e) {
            throw new RestException(400, $e->getMessage());
        }

        throw new RestException(501, 'This route is still under implementation');
    }

    /**
     * @url OPTIONS {id}/content
     *
     * @param int $id Id of the project
     */
    public function optionsContent(int $id): void
    {
        Header::allowOptionsGetPatch();
    }
}
