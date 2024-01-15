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
 */

declare(strict_types=1);

namespace Tuleap\Taskboard\REST\v1\Card;

use Luracast\Restler\RestException;
use PFUser;
use Tracker_Exception;
use Tracker_FormElement_Field_Numeric;
use Tracker_FormElement_InvalidFieldException;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_FormElementFactory;
use Tracker_NoChangeException;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\REST\I18NRestException;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
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
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;

class CardPatcher
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    private ArtifactUpdater $updater;

    public function __construct(
        Tracker_FormElementFactory $form_element_factory,
        ArtifactUpdater $updater,
    ) {
        $this->form_element_factory = $form_element_factory;

        $this->updater = $updater;
    }

    public static function build(): self
    {
        $usage_dao            = new ArtifactLinksUsageDao();
        $form_element_factory = \Tracker_FormElementFactory::instance();
        $artifact_factory     = \Tracker_ArtifactFactory::instance();
        $fields_retriever     = new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory);
        $event_dispatcher     = \EventManager::instance();

        $changeset_creator = new NewChangesetCreator(
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
            $fields_retriever,
            \EventManager::instance(),
            new \Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
            ArtifactChangesetSaver::build(),
            new ParentLinkAction($artifact_factory),
            new AfterNewChangesetHandler($artifact_factory, $fields_retriever),
            ActionsQueuer::build(\BackendLogger::getDefaultLogger()),
            new ChangesetValueSaver(),
            \WorkflowFactory::instance(),
            new CommentCreator(
                new \Tracker_Artifact_Changeset_CommentDao(),
                \ReferenceManager::instance(),
                new TrackerPrivateCommentUGroupPermissionInserter(new TrackerPrivateCommentUGroupPermissionDao()),
                new ChangesetCommentIndexer(
                    new ItemToIndexQueueEventBased($event_dispatcher),
                    $event_dispatcher,
                    new \Tracker_Artifact_Changeset_CommentDao(),
                ),
                new TextValueValidator(),
            )
        );

        $updater = new ArtifactUpdater(
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
        );

        return new self($form_element_factory, $updater);
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     */
    public function patchCard(Artifact $artifact, PFUser $user, CardPatchRepresentation $payload): void
    {
        $remaining_effort_field = $this->getRemainingEffortField($artifact, $user);
        $values                 = $this->getUpdateValues($payload, $remaining_effort_field);

        try {
            $this->updater->update($user, $artifact, $values);
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(500, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(500, $exception->getMessage());
        }
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     */
    private function getRemainingEffortField(
        Artifact $artifact,
        PFUser $user,
    ): Tracker_FormElement_Field_Numeric {
        $remaining_effort_field = $this->form_element_factory->getNumericFieldByNameForUser(
            $artifact->getTracker(),
            $user,
            \Tracker::REMAINING_EFFORT_FIELD_NAME
        );
        if (! $remaining_effort_field instanceof Tracker_FormElement_Field_Numeric) {
            throw new I18NRestException(
                400,
                dgettext('tuleap-taskboard', "The artifact does not have a remaining effort numeric field")
            );
        }
        if (! $remaining_effort_field->userCanUpdate($user)) {
            throw new RestException(403);
        }

        return $remaining_effort_field;
    }

    /**
     * @return ArtifactValuesRepresentation[]
     */
    private function getUpdateValues(
        CardPatchRepresentation $payload,
        Tracker_FormElement_Field_Numeric $remaining_effort_field,
    ): array {
        $representation           = new ArtifactValuesRepresentation();
        $representation->field_id = (int) $remaining_effort_field->getId();
        if ($remaining_effort_field instanceof \Tracker_FormElement_Field_Computed) {
            $representation->manual_value    = $payload->remaining_effort;
            $representation->is_autocomputed = false;
        } else {
            $representation->value = $payload->remaining_effort;
        }

        return [$representation];
    }
}
