<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

use BackendLogger;
use Docman_ItemFactory;
use EventManager;
use Luracast\Restler\RestException;
use PFUser;
use PluginManager;
use ProjectManager;
use ReferenceManager;
use Tracker_Artifact_Changeset_ChangesetDataInitializator;
use Tracker_Artifact_Changeset_CommentDao;
use Tracker_Artifact_Changeset_InitialChangesetFieldsValidator;
use Tracker_Artifact_Changeset_NewChangesetFieldsValidator;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use TrackerFactory;
use TransitionFactory;
use Tuleap\Artidoc\Adapter\Document\ArtidocRetriever;
use Tuleap\Artidoc\Adapter\Document\ArtidocWithContextDecorator;
use Tuleap\Artidoc\Adapter\Document\SearchArtidocDocumentDao;
use Tuleap\Artidoc\Adapter\Document\Section\AlreadyExistingSectionWithSameArtifactFault;
use Tuleap\Artidoc\Adapter\Document\Section\Artifact\ArtifactContentCreator;
use Tuleap\Artidoc\Adapter\Document\Section\Artifact\ArtifactContentUpdater;
use Tuleap\Artidoc\Adapter\Document\Section\DeleteOneSectionDao;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\UpdateFreetextContentDao;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\RequiredSectionInformationCollector;
use Tuleap\Artidoc\Adapter\Document\Section\RetrieveArtidocSectionDao;
use Tuleap\Artidoc\Adapter\Document\Section\SaveSectionDao;
use Tuleap\Artidoc\Adapter\Document\Section\UnableToFindSiblingSectionFault;
use Tuleap\Artidoc\Adapter\Document\Section\UpdateLevelDao;
use Tuleap\Artidoc\ArtidocWithContextRetrieverBuilder;
use Tuleap\Artidoc\Document\ArtidocDao;
use Tuleap\Artidoc\Document\ConfiguredTrackerRetriever;
use Tuleap\Artidoc\Document\DocumentServiceFromAllowedProjectRetriever;
use Tuleap\Artidoc\Document\Field\ConfiguredFieldCollectionBuilder;
use Tuleap\Artidoc\Document\Field\ConfiguredFieldDao;
use Tuleap\Artidoc\Document\Field\SuitableFieldRetriever;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\CannotUpdatePartiallyReadableDocumentFault;
use Tuleap\Artidoc\Domain\Document\Section\CollectRequiredSectionInformation;
use Tuleap\Artidoc\Domain\Document\Section\EmptyTitleFault;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\Artidoc\Domain\Document\Section\RetrievedSection;
use Tuleap\Artidoc\Domain\Document\Section\SectionCreator;
use Tuleap\Artidoc\Domain\Document\Section\SectionRetriever;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\InvalidSectionIdentifierStringException;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\SectionDeletor;
use Tuleap\Artidoc\Domain\Document\Section\SectionUpdater;
use Tuleap\Artidoc\Domain\Document\UserCannotWriteDocumentFault;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\NeverThrow\Fault;
use Tuleap\Notification\Mention\MentionedUserInTextRetriever;
use Tuleap\Option\Option;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\RESTLogger;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionDao;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\InitialChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetFieldValueSaver;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetPostProcessor;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetValidator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsQueuer;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactForwardLinksRetriever;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ArtifactLinksByChangesetCache;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ChangesetValueArtifactLinkDao;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksDao;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksRetriever;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksToNewChangesetsConverter;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValueSaver;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;
use Tuleap\Tracker\Artifact\Link\ArtifactReverseLinksUpdater;
use Tuleap\Tracker\FormElement\ArtifactLinkValidator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\Permission\SubmissionPermissionVerifier;
use Tuleap\Tracker\Permission\TrackersPermissionsRetriever;
use Tuleap\Tracker\REST\Artifact\ArtifactCreator;
use Tuleap\Tracker\REST\Artifact\ArtifactRestUpdateConditionsChecker;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataFromValuesByFieldBuilder;
use Tuleap\Tracker\REST\Artifact\CreateArtifact;
use Tuleap\Tracker\REST\Artifact\HandlePUT;
use Tuleap\Tracker\REST\Artifact\PUTHandler;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;
use UserManager;
use WorkflowFactory;
use WrapperLogger;

final class ArtidocSectionsResource extends AuthenticatedResource
{
    public const ROUTE = 'artidoc_sections';

    /**
     * @url OPTIONS {id}
     */
    public function options(string $id): void
    {
        Header::allowOptionsGetPutPostDelete();
    }

    /**
     * Get content of a section
     *
     * @url    GET {id}
     * @access hybrid
     *
     * @param string $id Uuid of the section
     *
     * @status 200
     * @throws RestException 404
     */
    public function get(string $id): SectionRepresentation
    {
        $this->checkAccess();

        try {
            $section_id = $this->getSectionIdentifierFactory()->buildFromHexadecimalString($id);
        } catch (InvalidSectionIdentifierStringException) {
            throw new RestException(404);
        }

        $user      = UserManager::instance()->getCurrentUser();
        $collector = new RequiredSectionInformationCollector(
            $user,
            new RequiredArtifactInformationBuilder(Tracker_ArtifactFactory::instance())
        );


        return $this->getSectionRetriever($user, $collector)
            ->retrieveSectionUserCanRead($section_id)
            ->andThen(fn(RetrievedSection $section) =>
                $this->getSectionRepresentationBuilder($section_id, $user)
                    ->getSectionRepresentation($section, $collector, $user))
                ->match(
                    fn(SectionRepresentation $representation) => $representation,
                    function () {
                        throw new RestException(404);
                    },
                );
    }

    /**
     * Update section
     *
     * Update the content of a section (title, description, and level)
     *
     * <p>Example payload, to update a section:</p>
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"title": "New title",<br>
     * &nbsp;&nbsp;"description": "New description",<br>
     * &nbsp;&nbsp;"attachments": [123, 124],<br>
     * &nbsp;&nbsp;"level": 1,<br>
     * }
     * </pre>
     *
     * <p><b>Note:</b> attachments field is only used for artifact section and will be ignored for freetext section.</p>
     *
     * @url    PUT {id}
     * @access hybrid
     *
     * @param string $id Uuid of the section
     * @param PUTSectionRepresentation $content New content of the section {@from body}
     *
     * @status 200
     * @throws RestException 404
     */
    public function put(string $id, PUTSectionRepresentation $content): void
    {
        $this->checkAccess();

        try {
            $section_id = $this->getSectionIdentifierFactory()->buildFromHexadecimalString($id);
        } catch (InvalidSectionIdentifierStringException) {
            throw new RestException(404);
        }

        $level = Level::tryFrom($content->level);
        if ($level === null) {
            throw new RestException(400, 'Unknown level. Allowed values: ' . implode(', ', Level::allowed()));
        }

        $user      = UserManager::instance()->getCurrentUser();
        $collector = new RequiredSectionInformationCollector(
            $user,
            new RequiredArtifactInformationBuilder(Tracker_ArtifactFactory::instance())
        );

        $updater = new SectionUpdater(
            $this->getSectionRetriever($user, $collector),
            new UpdateFreetextContentDao(new UpdateLevelDao()),
            new ArtifactContentUpdater(
                Tracker_ArtifactFactory::instance(),
                $this->getFileUploadDataProvider(),
                new UpdateLevelDao(),
                $this->getArtifactPutHandler(),
                $user,
            ),
        );

        $updater->update($section_id, $content->title, $content->description, $content->attachments, $level)
            ->mapErr(
                function (Fault $fault) {
                    throw match (true) {
                        $fault instanceof EmptyTitleFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'Title of the section cannot be empty.')
                        ),
                        $fault instanceof UserCannotWriteDocumentFault => new I18NRestException(
                            403,
                            dgettext('tuleap-artidoc', "You don't have permission to write the document.")
                        ),
                        default => new RestException(404),
                    };
                }
            );
    }

    /**
     * Delete section
     *
     * Delete the section of a document
     *
     * @url    DELETE {id}
     * @access hybrid
     *
     * @param string $id Uuid of the section
     *
     * @status 204
     * @throws RestException 404
     */
    public function delete(string $id): void
    {
        $this->checkAccess();

        try {
            $section_id = $this->getSectionIdentifierFactory()->buildFromHexadecimalString($id);
        } catch (InvalidSectionIdentifierStringException) {
            throw new RestException(404);
        }

        $user = UserManager::instance()->getCurrentUser();
        $this->getDeleteHandler($user)
            ->deleteSection($section_id)
            ->match(
                function () {
                },
                function () {
                    throw new RestException(404);
                },
            );
    }

    /**
     * Create section
     *
     * Create one section in an artidoc document.
     *
     * <p>Example payload, to create a section based on an existing artifact #123. The new section will be placed before its sibling:</p>
     * <pre>
     * {<br>
     * &nbsp;&nbsp;id: 456,<br>
     * &nbsp;&nbsp;section:{<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;"artifact": { "id": 123 },<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;"position": { "before": "550e8400-e29b-41d4-a716-446655440000" },<br>
     * &nbsp;&nbsp;}<br>
     * }
     * </pre>
     *
     * <p>Another example, if you want to put the section at the end of the document:</p>
     * <pre>
     * {<br>
     * &nbsp;&nbsp;id: 456,<br>
     * &nbsp;&nbsp;section:{<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;"artifact": { "id": 123 },<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;"position": null,<br>
     * &nbsp;&nbsp;}<br>
     * }
     * </pre>
     *
     *  <p>Example payload, to create a section based on freetext. The new section will be placed before its sibling:</p>
     *  <pre>
     * {<br>
     * &nbsp;&nbsp;id: 456,<br>
     * &nbsp;&nbsp;section:{<br>
     *  &nbsp;&nbsp;&nbsp;&nbsp;"content": { "title": "My title", "description": "My freetext description", type: "freetext", attachments: [] },<br>
     *  &nbsp;&nbsp;&nbsp;&nbsp;"position": { "before": "550e8400-e29b-41d4-a716-446655440000" },<br>
     * &nbsp;&nbsp;}<br>
     *  }
     *  </pre>
     *
     *  <p>Example payload, to create a section based on artifact. The artifact will be created in the configured tracker of the document.</p>
     *  <pre>
     * {<br>
     * &nbsp;&nbsp;id: 456,<br>
     * &nbsp;&nbsp;section:{<br>
     *  &nbsp;&nbsp;&nbsp;&nbsp;"content": { "title": "My title", "description": "My artifact description", type: "artifact", attachments: [] },<br>
     *  &nbsp;&nbsp;&nbsp;&nbsp;"position": { "before": "550e8400-e29b-41d4-a716-446655440000" },<br>
     * &nbsp;&nbsp;}<br>
     *  }
     *  </pre>
     *
     * @url    POST
     * @access hybrid
     *
     * @param int $artidoc_id Id of the document {@from body}
     * @param POSTSectionRepresentation $section {@from body}
     *
     * @status 200
     * @throws RestException
     */
    public function postSection(int $artidoc_id, POSTSectionRepresentation $section): SectionRepresentation
    {
        $this->checkAccess();

        $user = UserManager::instance()->getCurrentUser();

        $identifier_factory = new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());

        $collector = new RequiredSectionInformationCollector(
            $user,
            new RequiredArtifactInformationBuilder(Tracker_ArtifactFactory::instance())
        );

        try {
            $before_section_id = $section->position
                ? Option::fromValue($identifier_factory->buildFromHexadecimalString($section->position->before))
                : Option::nothing(SectionIdentifier::class);
        } catch (InvalidSectionIdentifierStringException) {
            throw new RestException(400, 'Sibling section id is invalid');
        }

        return $this->getSectionCreator($user, $collector)
            ->create($artidoc_id, $before_section_id, ContentToBeCreatedBuilder::buildFromRepresentation($section))
            ->andThen(
                fn (SectionIdentifier $section_identifier) =>
                $this->getSectionRetriever($user, $collector)
                    ->retrieveSectionUserCanRead($section_identifier)
            )->andThen(
                fn (RetrievedSection $section) =>
                $this->getSectionRepresentationBuilder($section->id, $user)
                    ->getSectionRepresentation($section, $collector, $user)
            )
            ->match(
                static fn(SectionRepresentation $representation) => $representation,
                static function (Fault $fault) {
                    throw match (true) {
                        $fault instanceof CannotUpdatePartiallyReadableDocumentFault => new RestException(404),
                        $fault instanceof UserCannotWriteDocumentFault => new I18NRestException(
                            403,
                            dgettext('tuleap-artidoc', "You don't have permission to write the document.")
                        ),
                        $fault instanceof AlreadyExistingSectionWithSameArtifactFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'There is already an existing section with the same artifact in the document.')
                        ),
                        $fault instanceof UnableToFindSiblingSectionFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'We were unable to insert the new section at the required position. The sibling section does not exist, maybe it has been deleted by someone else while you were editing the document?')
                        ),
                        default => new RestException(404, (string) $fault),
                    };
                }
            );
    }

    /**
     * @throws RestException
     */
    private function getSectionCreator(PFUser $user, CollectRequiredSectionInformation $collector): SectionCreator
    {
        $section_identifier_factory  = new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());
        $freetext_identifier_factory = new UUIDFreetextIdentifierFactory(new DatabaseUUIDV7Factory());

        return new SectionCreator(
            $this->getArtidocWithContextRetriever($user),
            new SaveSectionDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory()),
            new ArtifactContentCreator(
                new ConfiguredTrackerRetriever(
                    new ArtidocDao(
                        $section_identifier_factory,
                        $freetext_identifier_factory,
                    ),
                    TrackerFactory::instance(),
                    RESTLogger::getLogger(),
                ),
                $this->getFileUploadDataProvider(),
                $this->getArtifactPostHandler(),
                $user,
            ),
            $collector,
            new RetrieveArtidocSectionDao(
                $section_identifier_factory,
                $freetext_identifier_factory,
            ),
        );
    }

    private function getDeleteHandler(PFUser $user): SectionDeletor
    {
        return new SectionDeletor(
            new RetrieveArtidocSectionDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory()),
            $this->getArtidocWithContextRetriever($user),
            new DeleteOneSectionDao(),
        );
    }

    private function getSectionRetriever(PFUser $user, CollectRequiredSectionInformation $collector): SectionRetriever
    {
        return new SectionRetriever(
            new RetrieveArtidocSectionDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory()),
            $this->getArtidocWithContextRetriever($user),
            $collector,
        );
    }

    private function getSectionIdentifierFactory(): SectionIdentifierFactory
    {
        return new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());
    }

    private function getFreetextIdentifierFactory(): FreetextIdentifierFactory
    {
        return new UUIDFreetextIdentifierFactory(new DatabaseUUIDV7Factory());
    }

    private function getSectionRepresentationBuilder(
        SectionIdentifier $section_identifier,
        PFUser $user,
    ): SectionRepresentationBuilder {
        return new SectionRepresentationBuilder(
            $this->getArtifactSectionRepresentationBuilder($section_identifier, $user),
        );
    }

    private function getArtifactSectionRepresentationBuilder(
        SectionIdentifier $section_identifier,
        PFUser $user,
    ): ArtifactSectionRepresentationBuilder {
        $configured_field_collection_builder = new ConfiguredFieldCollectionBuilder(
            new ConfiguredFieldDao(),
            new SuitableFieldRetriever(Tracker_FormElementFactory::instance()),
        );
        return new ArtifactSectionRepresentationBuilder(
            $this->getFileUploadDataProvider(),
            new SectionFieldsBuilder(
                $configured_field_collection_builder->buildFromSectionIdentifier($section_identifier, $user),
            )
        );
    }

    private function getArtidocWithContextRetriever(PFUser $user): RetrieveArtidocWithContext
    {
        $plugin = PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $retriever_builder = new ArtidocWithContextRetrieverBuilder(
            new ArtidocRetriever(new SearchArtidocDocumentDao(), new Docman_ItemFactory()),
            new ArtidocWithContextDecorator(
                ProjectManager::instance(),
                new DocumentServiceFromAllowedProjectRetriever($plugin),
            ),
        );

        return $retriever_builder->buildForUser($user);
    }

    private function getArtifactPutHandler(): HandlePUT
    {
        $artifact_factory     = Tracker_ArtifactFactory::instance();
        $form_element_factory = Tracker_FormElementFactory::instance();

        $transaction_executor = new DBTransactionExecutorWithConnection(
            DBFactory::getMainTuleapDBConnection()
        );

        $usage_dao        = new ArtifactLinksUsageDao();
        $fields_retriever = new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory);
        $event_dispatcher = EventManager::instance();

        $changeset_comment_dao = new Tracker_Artifact_Changeset_CommentDao();

        $changeset_creator = new NewChangesetCreator(
            $transaction_executor,
            ArtifactChangesetSaver::build(),
            new AfterNewChangesetHandler($artifact_factory, $fields_retriever),
            WorkflowFactory::instance(),
            new CommentCreator(
                $changeset_comment_dao,
                ReferenceManager::instance(),
                new TrackerPrivateCommentUGroupPermissionInserter(new TrackerPrivateCommentUGroupPermissionDao()),
                new TextValueValidator(),
            ),
            new NewChangesetFieldValueSaver(
                $fields_retriever,
                new ChangesetValueSaver(),
            ),
            new NewChangesetValidator(
                new Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
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
                                new StateFactory(TransitionFactory::instance(), new SimpleWorkflowDao()),
                                new TransitionExtractor()
                            ),
                            FrozenFieldsRetriever::instance(),
                        )
                    )
                ),
                new Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory),
                new ParentLinkAction($artifact_factory),
            ),
            new NewChangesetPostProcessor(
                $event_dispatcher,
                ActionsQueuer::build(BackendLogger::getDefaultLogger()),
                new ChangesetCommentIndexer(
                    new ItemToIndexQueueEventBased($event_dispatcher),
                    $event_dispatcher,
                    $changeset_comment_dao,
                ),
                new MentionedUserInTextRetriever(UserManager::instance()),
            ),
        );

        $fields_data_builder       = new FieldsDataBuilder(
            $form_element_factory,
            new NewArtifactLinkChangesetValueBuilder(
                new ArtifactForwardLinksRetriever(
                    new ArtifactLinksByChangesetCache(),
                    new ChangesetValueArtifactLinkDao(),
                    $artifact_factory
                ),
            ),
            new NewArtifactLinkInitialChangesetValueBuilder(),
            TrackersPermissionsRetriever::build(),
        );
        $update_conditions_checker = new ArtifactRestUpdateConditionsChecker();

        $reverse_link_retriever = new ReverseLinksRetriever(
            new ReverseLinksDao(),
            $artifact_factory
        );

        return new PUTHandler(
            $fields_data_builder,
            new ArtifactReverseLinksUpdater(
                $reverse_link_retriever,
                new ReverseLinksToNewChangesetsConverter(
                    $form_element_factory,
                    $artifact_factory
                ),
                $changeset_creator
            ),
            $update_conditions_checker,
        );
    }

    private function getArtifactPostHandler(): CreateArtifact
    {
        $artifact_factory     = Tracker_ArtifactFactory::instance();
        $form_element_factory = Tracker_FormElementFactory::instance();
        $tracker_factory      = TrackerFactory::instance();

        $fields_retriever = new FieldsToBeSavedInSpecificOrderRetriever($form_element_factory);

        $artifact_link_initial_builder = new NewArtifactLinkInitialChangesetValueBuilder();

        $logger = new WrapperLogger(RESTLogger::getLogger(), self::class);

        return new ArtifactCreator(
            new FieldsDataBuilder(
                $form_element_factory,
                new NewArtifactLinkChangesetValueBuilder(
                    new ArtifactForwardLinksRetriever(
                        new ArtifactLinksByChangesetCache(),
                        new ChangesetValueArtifactLinkDao(),
                        $artifact_factory
                    )
                ),
                $artifact_link_initial_builder,
                TrackersPermissionsRetriever::build(),
            ),
            TrackerArtifactCreator::build(
                new InitialChangesetCreator(
                    Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build(),
                    $fields_retriever,
                    new \Tracker_Artifact_Changeset_ChangesetDataInitializator($form_element_factory),
                    $logger,
                    ArtifactChangesetSaver::build(),
                    new AfterNewChangesetHandler($artifact_factory, $fields_retriever),
                    \WorkflowFactory::instance(),
                    new InitialChangesetValueSaver(),
                ),
                Tracker_Artifact_Changeset_InitialChangesetFieldsValidator::build(),
                $logger,
            ),
            $tracker_factory,
            new FieldsDataFromValuesByFieldBuilder($form_element_factory, $artifact_link_initial_builder),
            $form_element_factory,
            SubmissionPermissionVerifier::instance(),
        );
    }

    private function getFileUploadDataProvider(): FileUploadDataProvider
    {
        $form_element_factory = Tracker_FormElementFactory::instance();

        return new FileUploadDataProvider(
            new FrozenFieldDetector(
                new TransitionRetriever(
                    new StateFactory(
                        TransitionFactory::instance(),
                        new SimpleWorkflowDao()
                    ),
                    new TransitionExtractor()
                ),
                new FrozenFieldsRetriever(
                    new FrozenFieldsDao(),
                    $form_element_factory
                )
            ),
            $form_element_factory
        );
    }
}
