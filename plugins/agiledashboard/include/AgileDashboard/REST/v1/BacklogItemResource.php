<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1;

use AgileDashboard_Milestone_Backlog_BacklogItem;
use AgileDashBoard_Semantic_InitialEffort;
use Luracast\Restler\RestException;
use PFUser;
use Tracker_Artifact_PriorityDao;
use Tracker_Artifact_PriorityHistoryDao;
use Tracker_Artifact_PriorityManager;
use Tracker_ArtifactDao;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tracker_Semantic_Status;
use Tracker_Semantic_Title;
use Tracker_SemanticCollection;
use Tracker_SemanticManager;
use TrackerFactory;
use Tuleap\AgileDashboard\RemainingEffortValueRetriever;
use Tuleap\AgileDashboard\REST\v1\Rank\ArtifactsRankOrderer;
use Tuleap\AgileDashboard\REST\v1\Scrum\BacklogItem\InitialEffortSemanticUpdater;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;
use Tuleap\Project\ProjectBackground\ProjectBackgroundDao;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\SlicedArtifactsBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdaterDataFormater;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;
use UserManager;

/**
 * Wrapper for Backlog_Items related REST methods
 */
class BacklogItemResource extends AuthenticatedResource
{
    public const MAX_LIMIT = 100;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var UserManager */
    private $user_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var ArtifactLinkUpdater */
    private $artifactlink_updater;

    /** @var ResourcesPatcher */
    private $resources_patcher;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var \Tuleap\AgileDashboard\RemainingEffortValueRetriever */
    private $remaining_effort_value_retriever;

    public function __construct()
    {
        $this->artifact_factory = Tracker_ArtifactFactory::instance();
        $this->user_manager     = UserManager::instance();

        $priority_manager = new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            $this->user_manager,
            $this->artifact_factory
        );

        $this->tracker_factory                  = TrackerFactory::instance();
        $this->artifactlink_updater             = new ArtifactLinkUpdater($priority_manager, new ArtifactLinkUpdaterDataFormater());
        $this->resources_patcher                = new ResourcesPatcher(
            $this->artifactlink_updater,
            $this->artifact_factory,
            $priority_manager
        );
        $this->form_element_factory             = Tracker_FormElementFactory::instance();
        $this->remaining_effort_value_retriever = new RemainingEffortValueRetriever(
            $this->form_element_factory
        );
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the BacklogItem
     */
    public function options($id)
    {
        $this->sendAllowHeader();
    }

    /**
     * Get backlog item
     *
     * Get a backlog item representation
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param int $id Id of the Backlog Item
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function get($id)
    {
        $this->checkAccess();
        $current_user = $this->getCurrentUser();
        $artifact     = $this->getArtifact($id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $current_user,
            $artifact->getTracker()->getProject()
        );

        $backlog_item                        = $this->getBacklogItem($current_user, $artifact);
        $backlog_item_representation_factory = $this->getBacklogItemRepresentationFactory();
        $backlog_item_representation         = $backlog_item_representation_factory->createBacklogItemRepresentation($backlog_item);

        $this->sendAllowHeader();

        return $backlog_item_representation;
    }

    private function getBacklogItem(PFUser $current_user, Artifact $artifact)
    {
        $semantic_manager = new Tracker_SemanticManager($artifact->getTracker());
        $semantics        = $semantic_manager->getSemantics();

        $artifact     = $this->updateArtifactTitleSemantic($current_user, $artifact, $semantics);
        $backlog_item = new AgileDashboard_Milestone_Backlog_BacklogItem($artifact, false);
        $backlog_item = $this->updateBacklogItemStatusSemantic($current_user, $artifact, $backlog_item, $semantics);

        $initial_effort_updater = new InitialEffortSemanticUpdater();
        $backlog_item           = $initial_effort_updater->updateBacklogItemInitialEffortSemantic(
            $current_user,
            $backlog_item,
            $semantics[AgileDashBoard_Semantic_InitialEffort::NAME]
        );
        $backlog_item           = $this->updateBacklogItemRemainingEffort($current_user, $backlog_item);
        $parent_artifact        = $artifact->getParent($current_user);
        if ($parent_artifact !== null) {
            $backlog_item->setParent($parent_artifact);
        }

        return $backlog_item;
    }

    private function updateArtifactTitleSemantic(PFUser $current_user, Artifact $artifact, Tracker_SemanticCollection $semantics)
    {
        $semantic_title = $semantics[Tracker_Semantic_Title::NAME];
        $title_field    = $semantic_title->getField();

        if ($title_field && $title_field->userCanRead($current_user)) {
            $artifact->setTitle($title_field->getRESTValue($current_user, $artifact->getLastChangeset())->value);
        }

        return $artifact;
    }

    private function updateBacklogItemStatusSemantic(
        PFUser $current_user,
        Artifact $artifact,
        AgileDashboard_Milestone_Backlog_BacklogItem $backlog_item,
        Tracker_SemanticCollection $semantics,
    ) {
        $semantic_status = $semantics[Tracker_Semantic_Status::NAME];

        if (
            $semantic_status && $semantic_status->getField() && $semantic_status->getField()->userCanRead(
                $current_user
            )
        ) {
            $label = $semantic_status->getNormalizedStatusLabel($artifact);

            if ($label) {
                $backlog_item->setStatus($artifact->getStatus(), $label);
            }
        }

        return $backlog_item;
    }

    private function updateBacklogItemRemainingEffort(
        PFUser $current_user,
        AgileDashboard_Milestone_Backlog_BacklogItem $backlog_item,
    ) {
        $backlog_item->setRemainingEffort(
            $this->remaining_effort_value_retriever->getRemainingEffortValue($current_user, $backlog_item->getArtifact())
        );

        return $backlog_item;
    }

    /**
     * Get children
     *
     * Get the children of a given Backlog Item
     *
     * @url GET {id}/children
     * @access hybrid
     *
     * @param int $id     Id of the Backlog Item
     * @param int $limit  Number of elements displayed per page {@min 0} {@max 100}
     * @param int $offset Position of the first element to display{ @min 0}
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentation}
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    public function getChildren($id, $limit = 10, $offset = 0)
    {
        $this->checkAccess();

        $current_user = $this->getCurrentUser();
        $artifact     = $this->getArtifact($id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $current_user,
            $artifact->getTracker()->getProject()
        );

        $backlog_items_representations       = [];
        $backlog_item_representation_factory = $this->getBacklogItemRepresentationFactory();

        $sliced_children = $this->getSlicedArtifactsBuilder()->getSlicedChildrenArtifactsForUser($artifact, $this->getCurrentUser(), $limit, $offset);

        foreach ($sliced_children->getArtifacts() as $child) {
            $backlog_item                    = $this->getBacklogItem($current_user, $child->getArtifact());
            $backlog_items_representations[] = $backlog_item_representation_factory->createBacklogItemRepresentation($backlog_item);
        }

        $this->sendAllowHeaderForChildren();
        $this->sendPaginationHeaders($limit, $offset, $sliced_children->getTotalSize());

        return $backlog_items_representations;
    }

    /**
     * Partial re-order of backlog items plus update of children
     *
     * Define the priorities of some children of a given Backlog Item
     *
     * <br>
     * Example:
     * <pre>
     * "order": {
     *   "ids" : [123, 789, 1001],
     *   "direction": "before",
     *   "compared_to": 456
     * }
     * </pre>
     *
     * <br>
     * Resulting order will be: <pre>[…, 123, 789, 1001, 456, …]</pre>
     *
     * <br>
     * Add example:
     * <pre>
     * "add": [
     *   {
     *     "id": 34
     *     "remove_from": 56
     *   },
     *   ...
     * ]
     * </pre>
     *
     * <br>
     * Will remove element id 34 from 56 children and add it to current backlog_items children
     *
     * @url PATCH {id}/children
     *
     * @param int                                                   $id    Id of the Backlog Item
     * @param \Tuleap\AgileDashboard\REST\v1\OrderRepresentation    $order Order of the children {@from body}
     * @param array                                                 $add   Ids to add to backlog_items content  {@from body} {@type BacklogAddRepresentation}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 409
     */
    protected function patch($id, ?OrderRepresentation $order = null, ?array $add = null)
    {
        $artifact = $this->getArtifact($id);
        $user     = $this->getCurrentUser();
        $project  = $artifact->getTracker()->getProject();

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        try {
            $indexed_children_ids = $this->getChildrenArtifactIds($user, $artifact);

            if ($add) {
                $this->resources_patcher->startTransaction();
                $to_add = $this->resources_patcher->removeArtifactFromSource($user, $add);
                if (count($to_add)) {
                    $validator         = new PatchAddRemoveValidator(
                        $indexed_children_ids,
                        new PatchAddBacklogItemsValidator(
                            $this->artifact_factory,
                            $this->tracker_factory->getPossibleChildren($artifact->getTracker()),
                            $id
                        )
                    );
                    $backlog_items_ids = $validator->validate($id, [], $to_add);

                    $this->artifactlink_updater->updateArtifactLinks(
                        $user,
                        $artifact,
                        $backlog_items_ids,
                        [],
                        \Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD
                    );
                    $indexed_children_ids = array_flip($backlog_items_ids);
                }
                $this->resources_patcher->commit();
            }

            if ($order) {
                $order->checkFormat();
                $order_validator = new OrderValidator($indexed_children_ids);
                $order_validator->validate($order);

                $orderer = ArtifactsRankOrderer::build();
                $orderer->reorder($order, (string) $id, $project);
            }
        } catch (IdsFromBodyAreNotUniqueException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (OrderIdOutOfBoundException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (ArtifactCannotBeChildrenOfException $exception) {
            throw new RestException(409, $exception->getMessage());
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    private function getArtifact($id)
    {
        $artifact     = $this->artifact_factory->getArtifactById($id);
        $current_user = $this->getCurrentUser();

        if (! $artifact) {
            throw new RestException(404, 'Backlog Item not found');
        } elseif (! $artifact->userCanView($current_user)) {
            throw new RestException(403, 'You cannot access to this backlog item');
        }

        return $artifact;
    }

    private function getChildrenArtifactIds(PFUser $user, Artifact $artifact)
    {
        $linked_artifacts_index = [];
        foreach ($artifact->getChildrenForUser($user) as $artifact) {
            $linked_artifacts_index[$artifact->getId()] = true;
        }
        return $linked_artifacts_index;
    }

    /**
     * @url OPTIONS {id}/children
     *
     * @param int $id Id of the BacklogItem
     *
     * @throws RestException 404
     */
    public function optionsChildren($id)
    {
        $this->sendAllowHeaderForChildren();
    }

    private function getSlicedArtifactsBuilder()
    {
        return new SlicedArtifactsBuilder(new Tracker_ArtifactDao(), Tracker_ArtifactFactory::instance());
    }

    private function sendAllowHeader()
    {
        Header::allowOptionsGet();
    }

    private function sendAllowHeaderForChildren()
    {
        Header::allowOptionsGetPatch();
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function getCurrentUser()
    {
        return $this->user_manager->getCurrentUser();
    }

    private function getBacklogItemRepresentationFactory()
    {
        $color_builder = new BackgroundColorBuilder(new BindDecoratorRetriever());
        return new BacklogItemRepresentationFactory(
            $color_builder,
            $this->user_manager,
            new ProjectBackgroundConfiguration(new ProjectBackgroundDao())
        );
    }
}
