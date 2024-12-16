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
 */

declare(strict_types=1);

namespace Tuleap\Taskboard\REST\v1\Cell;

use Cardwall_OnTop_ColumnDao;
use Luracast\Restler\RestException;
use PFUser;
use Tracker_ArtifactFactory;
use TrackerFactory;
use Tuleap\Cardwall\OnTop\Config\ColumnFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Notification\Mention\MentionedUserInTextRetriever;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Taskboard\Column\CardColumnFinder;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\ArtifactMappedFieldValueRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldValuesRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappingDao;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValuesRetriever;
use Tuleap\Taskboard\Column\MilestoneTrackerRetriever;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetFieldValueSaver;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetPostProcessor;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetValidator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsQueuer;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactForwardLinksRetriever;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactLinksByChangesetCache;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangesetValueArtifactLinkDao;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\REST\Artifact\ArtifactRestUpdateConditionsChecker;
use Tuleap\Tracker\REST\Artifact\ArtifactUpdater;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\Helpers\IdsFromBodyAreNotUniqueException;
use Tuleap\Tracker\REST\Helpers\OrderIdOutOfBoundException;
use Tuleap\Tracker\REST\Helpers\OrderRepresentation;
use Tuleap\Tracker\REST\Helpers\OrderValidator;
use Tuleap\Tracker\REST\Helpers\ArtifactsRankOrderer;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\Taskboard\Swimlane\SwimlaneChildrenRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Rule\FirstValidValueAccordingToDependenciesRetriever;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\ValidValuesAccordingToTransitionsRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;
use UserManager;
use Workflow_Transition_ConditionFactory;

class CellPatcher
{
    /** @var UserManager */
    private $user_manager;
    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;
    /** @var SwimlaneChildrenRetriever */
    private $children_retriever;
    /** @var ArtifactsRankOrderer */
    private $rank_orderer;
    /** @var CardMappedFieldUpdater */
    private $mapped_field_updater;

    public function __construct(
        UserManager $user_manager,
        Tracker_ArtifactFactory $artifact_factory,
        SwimlaneChildrenRetriever $children_retriever,
        ArtifactsRankOrderer $rank_orderer,
        CardMappedFieldUpdater $mapped_field_updater,
    ) {
        $this->user_manager         = $user_manager;
        $this->artifact_factory     = $artifact_factory;
        $this->children_retriever   = $children_retriever;
        $this->rank_orderer         = $rank_orderer;
        $this->mapped_field_updater = $mapped_field_updater;
    }

    public static function build(): self
    {
        $artifact_factory         = Tracker_ArtifactFactory::instance();
        $usage_dao                = new ArtifactLinksUsageDao();
        $form_element_factory     = \Tracker_FormElementFactory::instance();
        $fields_retriever         = new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory);
        $event_dispatcher         = \EventManager::instance();
        $changeset_comment_dao    = new \Tracker_Artifact_Changeset_CommentDao();
        $column_dao               = new Cardwall_OnTop_ColumnDao();
        $freestyle_mapping_dao    = new FreestyleMappingDao();
        $semantic_status_provider = new \Cardwall_FieldProviders_SemanticStatusFieldRetriever();
        $column_factory           = new ColumnFactory($column_dao);

        $mapped_field_retriever  = new MappedFieldRetriever(
            $semantic_status_provider,
            new FreestyleMappedFieldRetriever($freestyle_mapping_dao, $form_element_factory)
        );
        $mapped_values_retriever = new MappedValuesRetriever(
            new FreestyleMappedFieldValuesRetriever($freestyle_mapping_dao, $freestyle_mapping_dao),
            $semantic_status_provider
        );

        $user_manager      = UserManager::instance();
        $changeset_creator = new NewChangesetCreator(
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            ArtifactChangesetSaver::build(),
            new AfterNewChangesetHandler($artifact_factory, $fields_retriever),
            \WorkflowFactory::instance(),
            new CommentCreator(
                $changeset_comment_dao,
                \ReferenceManager::instance(),
                new TrackerPrivateCommentUGroupPermissionInserter(new TrackerPrivateCommentUGroupPermissionDao()),
                new TextValueValidator(),
            ),
            new NewChangesetFieldValueSaver(
                $fields_retriever,
                new ChangesetValueSaver(),
            ),
            new NewChangesetValidator(
                new \Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
                    $form_element_factory,
                    new ArtifactLinkValidator(
                        $artifact_factory,
                        new TypePresenterFactory(new TypeDao(), $usage_dao),
                        $usage_dao,
                        $event_dispatcher,
                    ),
                    new WorkflowUpdateChecker(
                        new FrozenFieldDetector(
                            new TransitionRetriever(
                                new StateFactory(\TransitionFactory::instance(), new SimpleWorkflowDao()),
                                new TransitionExtractor()
                            ),
                            FrozenFieldsRetriever::instance(),
                        )
                    )
                ),
                new \Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory),
                new ParentLinkAction($artifact_factory),
            ),
            new NewChangesetPostProcessor(
                $event_dispatcher,
                ActionsQueuer::build(\BackendLogger::getDefaultLogger()),
                new ChangesetCommentIndexer(
                    new ItemToIndexQueueEventBased($event_dispatcher),
                    $event_dispatcher,
                    $changeset_comment_dao,
                ),
                new MentionedUserInTextRetriever($user_manager),
            ),
        );

        return new CellPatcher(
            UserManager::instance(),
            $artifact_factory,
            new SwimlaneChildrenRetriever(),
            ArtifactsRankOrderer::build(),
            new CardMappedFieldUpdater(
                $column_factory,
                new MilestoneTrackerRetriever($column_dao, TrackerFactory::instance()),
                new AddValidator(),
                $mapped_field_retriever,
                $mapped_values_retriever,
                new FirstPossibleValueInListRetriever(
                    new FirstValidValueAccordingToDependenciesRetriever(
                        $form_element_factory
                    ),
                    new ValidValuesAccordingToTransitionsRetriever(
                        Workflow_Transition_ConditionFactory::build()
                    )
                ),
                new CardColumnFinder(
                    new ArtifactMappedFieldValueRetriever($mapped_field_retriever),
                    $column_factory,
                    $mapped_values_retriever
                ),
                new ArtifactUpdater(
                    new FieldsDataBuilder(
                        $form_element_factory,
                        new NewArtifactLinkChangesetValueBuilder(
                            new ArtifactForwardLinksRetriever(
                                new ArtifactLinksByChangesetCache(),
                                new ChangesetValueArtifactLinkDao(),
                                $artifact_factory
                            )
                        ),
                        new NewArtifactLinkInitialChangesetValueBuilder()
                    ),
                    $changeset_creator,
                    new ArtifactRestUpdateConditionsChecker(),
                ),
            )
        );
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     */
    public function patchCell(int $swimlane_id, int $column_id, CellPatchRepresentation $payload): void
    {
        $current_user      = $this->user_manager->getCurrentUser();
        $swimlane_artifact = $this->getSwimlaneArtifact($current_user, $swimlane_id);
        $project           = $swimlane_artifact->getTracker()->getProject();
        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt($project);

        $payload->checkIsValid();

        if ($payload->add !== null) {
            $artifact_to_add = $this->getArtifactToAdd($current_user, $payload->add);
            $this->mapped_field_updater->updateCardMappedField(
                $swimlane_artifact,
                $column_id,
                $artifact_to_add,
                $current_user
            );
        }

        $order = $payload->order;
        if ($order !== null) {
            $order->checkFormat();
            $this->validateOrder($order, $current_user, $swimlane_artifact);
            $this->rank_orderer->reorder($order, \Tracker_Artifact_PriorityHistoryChange::NO_CONTEXT, $project);
        }
    }

    /**
     * @throws I18NRestException
     */
    private function getArtifactToAdd(PFUser $current_user, int $id): Artifact
    {
        $artifact = $this->artifact_factory->getArtifactById($id);
        if (! $artifact || ! $artifact->userCanView($current_user)) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-taskboard', 'Could not find artifact to add with id %d.'),
                    $id
                )
            );
        }
        return $artifact;
    }

    /**
     * @throws RestException
     */
    private function getSwimlaneArtifact(PFUser $current_user, int $id): Artifact
    {
        $artifact = $this->artifact_factory->getArtifactById($id);
        if (! $artifact || ! $artifact->userCanView($current_user)) {
            throw new RestException(404);
        }

        return $artifact;
    }

    /**
     * @throws RestException
     */
    private function validateOrder(
        OrderRepresentation $order,
        PFUser $current_user,
        Artifact $swimlane_artifact,
    ): void {
        $children_artifact_ids          = $this->children_retriever->getSwimlaneArtifactIds(
            $swimlane_artifact,
            $current_user
        );
        $index_of_swimlane_children_ids = array_fill_keys($children_artifact_ids, true);
        $order_validator                = new OrderValidator($index_of_swimlane_children_ids);
        try {
            $order_validator->validate($order);
        } catch (IdsFromBodyAreNotUniqueException | OrderIdOutOfBoundException $e) {
            throw new RestException(400, $e->getMessage());
        }
    }
}
