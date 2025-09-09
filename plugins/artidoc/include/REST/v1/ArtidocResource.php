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

use Codendi_HTMLPurifier;
use DateTimeImmutable;
use Docman_ItemFactory;
use Docman_PermissionsManager;
use EventManager;
use Luracast\Restler\RestException;
use ProjectManager;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\ArtidocRetriever;
use Tuleap\Artidoc\Adapter\Document\ArtidocWithContextDecorator;
use Tuleap\Artidoc\Adapter\Document\SearchArtidocDocumentDao;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\ReorderSectionsDao;
use Tuleap\Artidoc\Adapter\Document\Section\RequiredSectionInformationCollector;
use Tuleap\Artidoc\Adapter\Document\Section\RetrieveArtidocSectionDao;
use Tuleap\Artidoc\ArtidocWithContextRetrieverBuilder;
use Tuleap\Artidoc\Document\ArtidocDao;
use Tuleap\Artidoc\Document\ConfigurationSaver;
use Tuleap\Artidoc\Document\DocumentServiceFromAllowedProjectRetriever;
use Tuleap\Artidoc\Document\Field\ArtifactLink\ArtifactLinkFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\ConfiguredFieldCollectionBuilder;
use Tuleap\Artidoc\Document\Field\ConfiguredFieldDao;
use Tuleap\Artidoc\Document\Field\Date\DateFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\FieldsWithValuesBuilder;
use Tuleap\Artidoc\Document\Field\List\ListFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\List\StaticListFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\List\UserGroupListWithValueBuilder;
use Tuleap\Artidoc\Document\Field\List\UserListFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\Numeric\NumericFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\Permissions\PermissionsOnArtifactFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\StepsDefinition\StepsDefinitionFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\StepsExecution\StepsExecutionFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Field\SuitableFieldRetriever;
use Tuleap\Artidoc\Document\Field\User\UserFieldWithValueBuilder;
use Tuleap\Artidoc\Document\Tracker\NoSemanticDescriptionFault;
use Tuleap\Artidoc\Document\Tracker\NoSemanticTitleFault;
use Tuleap\Artidoc\Document\Tracker\SemanticTitleIsNotAStringFault;
use Tuleap\Artidoc\Document\Tracker\SuitableTrackerForDocumentChecker;
use Tuleap\Artidoc\Document\Tracker\TooManyRequiredFieldsFault;
use Tuleap\Artidoc\Document\Tracker\TrackerNotFoundFault;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\EmptyDocumentFault;
use Tuleap\Artidoc\Domain\Document\Order\CannotMoveSectionRelativelyToItselfFault;
use Tuleap\Artidoc\Domain\Document\Order\ChangeSectionOrder;
use Tuleap\Artidoc\Domain\Document\Order\CompareToIsNotAChildSectionChecker;
use Tuleap\Artidoc\Domain\Document\Order\CompareToSectionIsAChildSectionFault;
use Tuleap\Artidoc\Domain\Document\Order\Direction;
use Tuleap\Artidoc\Domain\Document\Order\InvalidComparedToFault;
use Tuleap\Artidoc\Domain\Document\Order\InvalidDirectionFault;
use Tuleap\Artidoc\Domain\Document\Order\InvalidIdsFault;
use Tuleap\Artidoc\Domain\Document\Order\SectionChildrenBuilder;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrder;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrderBuilder;
use Tuleap\Artidoc\Domain\Document\Order\UnableToReorderSectionOutsideOfDocumentFault;
use Tuleap\Artidoc\Domain\Document\Order\UnknownSectionToMoveFault;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldDoesNotBelongToTrackerFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldIsDescriptionSemanticFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldIsTitleSemanticFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldNotFoundFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldNotSupportedFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\LinkFieldMustBeDisplayedInBlockFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\StepsDefinitionFieldMustBeDisplayedInBlockFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\StepsExecutionFieldMustBeDisplayedInBlockFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\TextFieldMustBeDisplayedInBlockFault;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\PaginatedRetrievedSections;
use Tuleap\Artidoc\Domain\Document\Section\PaginatedRetrievedSectionsRetriever;
use Tuleap\Artidoc\Domain\Document\UserCannotWriteDocumentFault;
use Tuleap\Artidoc\REST\v1\ArtifactSection\ArtifactSectionRepresentationBuilder;
use Tuleap\Artidoc\REST\v1\ArtifactSection\RequiredArtifactInformationBuilder;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\REST\v1\DocmanItemsEventAdder;
use Tuleap\Docman\REST\v1\DocmanItemsRequestBuilder;
use Tuleap\Docman\REST\v1\DocmanPATCHItemRepresentation;
use Tuleap\Docman\REST\v1\MoveItem\BeforeMoveVisitor;
use Tuleap\Docman\REST\v1\MoveItem\DocmanItemMover;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadDAO;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\NeverThrow\Fault;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
use Tuleap\Tracker\Artifact\Dao\PriorityDao;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\SystemTypePresenterBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Semantic\Description\CachedSemanticDescriptionFieldRetriever;
use Tuleap\Tracker\Semantic\Status\CachedSemanticStatusRetriever;
use Tuleap\Tracker\Semantic\Title\CachedSemanticTitleFieldRetriever;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use UserHelper;
use UserManager;

final class ArtidocResource extends AuthenticatedResource
{
    public const string ROUTE   = 'artidoc';
    private const int MAX_LIMIT = 50;

    /**
     * @url OPTIONS {id}
     */
    public function options(int $id): void
    {
        Header::allowOptionsPatch();
    }

    /**
     * Move an existing artidoc document
     *
     * @url    PATCH {id}
     * @access hybrid
     *
     * @param int                           $id             Id of the item
     * @param DocmanPATCHItemRepresentation $representation {@from body}
     *
     * @status 200
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */

    public function patch(int $id, DocmanPATCHItemRepresentation $representation): void
    {
        $this->checkAccess();

        $user_manager    = UserManager::instance();
        $request_builder = new DocmanItemsRequestBuilder($user_manager, ProjectManager::instance());

        $item_request = $request_builder->buildFromItemId($id);
        $project      = $item_request->getProject();
        $this->addAllEvent($project);

        $item_factory = new Docman_ItemFactory();
        $item_mover   = new DocmanItemMover(
            $item_factory,
            new BeforeMoveVisitor(
                new DoesItemHasExpectedTypeVisitor(ArtidocDocument::class),
                $item_factory,
                new DocumentOngoingUploadRetriever(new DocumentOngoingUploadDAO())
            ),
            $this->getPermissionManager($project),
            EventManager::instance(),
        );

        $item_mover->moveItem(
            new DateTimeImmutable(),
            $item_request->getItem(),
            $user_manager->getCurrentUser(),
            $representation->move
        );
    }

    private function addAllEvent(\Project $project): void
    {
        $event_adder = new DocmanItemsEventAdder(EventManager::instance());
        $event_adder->addLogEvents();
        $event_adder->addNotificationEvents($project);
    }

    private function getPermissionManager(\Project $project): Docman_PermissionsManager
    {
        return Docman_PermissionsManager::instance($project->getGroupId());
    }

    /**
     * @url OPTIONS {id}/sections
     */
    public function optionsSections(int $id): void
    {
        Header::allowOptionsGetPostPatch();
    }

    /**
     * Get sections
     *
     * Get sections of an artidoc document
     *
     * @url    GET {id}/sections
     * @access hybrid
     *
     * @param int $id Id of the document
     * @param int $limit Number of elements displayed {@from path}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return SectionRepresentation[]
     *
     * @status 200
     * @throws RestException
     */
    public function getSections(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $this->checkAccess();

        $user = UserManager::instance()->getCurrentUser();

        return $this->getPaginatedRetrievedSectionsRetriever($user)
            ->retrievePaginatedRetrievedSections($id, $limit, $offset)
            ->andThen(fn (PaginatedRetrievedSections $retrieved_sections) =>
                $this->getRepresentationTransformer($retrieved_sections->artidoc, $user)->getRepresentation($retrieved_sections, $user))->match(
                    function (PaginatedArtidocSectionRepresentationCollection $collection) use ($limit, $offset) {
                        Header::sendPaginationHeaders($limit, $offset, $collection->total, self::MAX_LIMIT);
                        return $collection->sections;
                    },
                    function () {
                        throw new RestException(404);
                    },
                );
    }

    /**
     * Reorder sections
     *
     * Reorder sections of a document.
     *
     * <p>Note: only one section can be moved for now.</p>
     *
     * <p>Example payload to move section with id "123" before the section with id "124":</p>
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"order": {<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;"ids": ["123"],<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;"direction": "before",<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;"compared_to": "124"<br>
     * &nbsp;&nbsp;}<br>
     * }
     * </pre>
     *
     * @url    PATCH {id}/sections
     * @access hybrid
     *
     * @param int $id Id of the document
     * @param \Tuleap\Artidoc\REST\v1\OrderRepresentation $order Order of the sections {@from body}
     *
     * @status 200
     * @throws RestException
     */
    public function patchSections(int $id, OrderRepresentation $order): void
    {
        $this->checkAccess();

        $user = UserManager::instance()->getCurrentUser();

        $this->getSectionOrderBuilder()
            ->build($order->ids, $order->direction, $order->compared_to)
            ->andThen(fn (SectionOrder $order) => $this->getReorderHandler($user)->reorder($id, $order))
            ->mapErr(
                static function (Fault $fault) use ($order) {
                    throw match (true) {
                        $fault instanceof UserCannotWriteDocumentFault => new I18NRestException(
                            403,
                            dgettext('tuleap-artidoc', "You don't have permission to write the document.")
                        ),
                        $fault instanceof CannotMoveSectionRelativelyToItselfFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'The section cannot be moved relatively to itself.'),
                        ),
                        $fault instanceof InvalidComparedToFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'The compared_to is not a valid section identifier.'),
                        ),
                        $fault instanceof InvalidDirectionFault => new I18NRestException(
                            400,
                            sprintf(
                                dgettext('tuleap-artidoc', 'Unknown direction "%s". Expected one of the following values: %s'),
                                $order->direction,
                                implode(', ', Direction::allowed()),
                            ),
                        ),
                        $fault instanceof InvalidIdsFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'The ids is invalid. Expected an array of one section identifier.'),
                        ),
                        $fault instanceof UnableToReorderSectionOutsideOfDocumentFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'The section cannot be reordered outside of its document.'),
                        ),
                        $fault instanceof UnknownSectionToMoveFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'The section being moved does not belong to the document.'),
                        ),
                        $fault instanceof CompareToSectionIsAChildSectionFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'You cannot move a parent section within its child sections.'),
                        ),
                        $fault instanceof EmptyDocumentFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'This document does not contain any sections.'),
                        ),
                        default => new RestException(404, (string) $fault),
                    };
                }
            );
    }

    /**
     * @url OPTIONS {id}/configuration
     */
    public function optionsConfiguration(int $id): void
    {
        Header::allowOptionsPut();
    }

    /**
     * Set configuration
     *
     * Update the configuration of an artidoc document.
     *
     * <p>Payload example:</p>
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"selected_tracker_ids": [ 123 ],<br>
     * &nbsp;&nbsp;"fields": [<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"field_id": 1001,<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"display_type": "column"<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;},<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;{<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"field_id": 1002,<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"display_type": "block"<br>
     * &nbsp;&nbsp;&nbsp;&nbsp;}<br>
     * &nbsp;&nbsp;]<br>
     * }
     * </pre>
     *
     * @url    PUT {id}/configuration
     * @access hybrid
     *
     * @param int $id Id of the document
     * @param PUTConfigurationRepresentation $configuration {@from body}
     *
     * @status 200
     * @throws RestException
     */
    public function putConfiguration(int $id, PUTConfigurationRepresentation $configuration): void
    {
        $this->checkAccess();

        $user = UserManager::instance()->getCurrentUser();
        $this->getPutConfigurationHandler($user)
            ->handle($id, $configuration, $user)
            ->mapErr(
                function (Fault $fault) {
                    throw match ($fault::class) {
                        FieldNotFoundFault::class => new I18NRestException(
                            400,
                            sprintf(dgettext('tuleap-artidoc', 'The field with id #%s could not be found.'), $fault->field_id),
                        ),
                        FieldIsDescriptionSemanticFault::class => new I18NRestException(
                            400,
                            sprintf(dgettext('tuleap-artidoc', 'The field with id #%s is already used in description semantic, it cannot be reused in fields for artidoc.'), $fault->field_id)
                        ),
                        FieldIsTitleSemanticFault::class => new I18NRestException(
                            400,
                            sprintf(dgettext('tuleap-artidoc', 'The field with id #%s is already used in title semantic, it cannot be reused in fields for artidoc.'), $fault->field_id)
                        ),
                        FieldNotSupportedFault::class => new I18NRestException(
                            400,
                            sprintf(dgettext('tuleap-artidoc', 'The field with id #%s is not supported in Artidoc.'), $fault->field_id)
                        ),
                        FieldDoesNotBelongToTrackerFault::class => new I18NRestException(
                            400,
                            sprintf(dgettext('tuleap-artidoc', 'The field with id #%s must belong to the selected tracker.'), $fault->field_id),
                        ),
                        LinkFieldMustBeDisplayedInBlockFault::class => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', "Artifact link field must use 'block' display type."),
                        ),
                        TextFieldMustBeDisplayedInBlockFault::class => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', "Text field must use 'block' display type."),
                        ),
                        StepsDefinitionFieldMustBeDisplayedInBlockFault::class => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', "Steps definition field must use 'block' display type."),
                        ),
                        StepsExecutionFieldMustBeDisplayedInBlockFault::class => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', "Steps execution field must use 'block' display type."),
                        ),
                        TrackerNotFoundFault::class => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', "Given tracker cannot be found or you don't have access to it.")
                        ),
                        NoSemanticTitleFault::class => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'Given tracker does not have a semantic title.')
                        ),
                        NoSemanticDescriptionFault::class => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'Given tracker does not have a semantic description.')
                        ),
                        SemanticTitleIsNotAStringFault::class => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'The semantic title should be a string field.')
                        ),
                        TooManyRequiredFieldsFault::class => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'There cannot be other required fields than title or description.')
                        ),
                        UserCannotWriteDocumentFault::class => new I18NRestException(
                            403,
                            dgettext('tuleap-artidoc', "You don't have permission to write the document.")
                        ),
                        default => new RestException(404),
                    };
                }
            );
    }

    private function getPaginatedRetrievedSectionsRetriever(\PFUser $user): PaginatedRetrievedSectionsRetriever
    {
        return new PaginatedRetrievedSectionsRetriever(
            $this->getArtidocWithContextRetriever($user),
            new RetrieveArtidocSectionDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory()),
        );
    }

    /**
     * @throws RestException
     */
    private function getReorderHandler(\PFUser $user): ChangeSectionOrder
    {
        return new ChangeSectionOrder(
            $this->getArtidocWithContextRetriever($user),
            new ReorderSectionsDao(),
            new SectionChildrenBuilder(
                new RetrieveArtidocSectionDao(
                    $this->getSectionIdentifierFactory(),
                    $this->getFreetextIdentifierFactory()
                )
            ),
            new CompareToIsNotAChildSectionChecker()
        );
    }

    /**
     * @throws RestException
     */
    private function getPutConfigurationHandler(\PFUser $user): PUTConfigurationHandler
    {
        $form_element_factory        = \Tracker_FormElementFactory::instance();
        $description_field_retriever = CachedSemanticDescriptionFieldRetriever::instance();
        $title_field_retriever       = CachedSemanticTitleFieldRetriever::instance();

        return new PUTConfigurationHandler(
            $this->getArtidocWithContextRetriever($user),
            new ConfigurationSaver(
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                new ArtidocDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory()),
                new ConfiguredFieldDao(),
            ),
            \TrackerFactory::instance(),
            new SuitableTrackerForDocumentChecker(
                $form_element_factory,
                $description_field_retriever,
                $title_field_retriever,
            ),
            new SuitableFieldRetriever(
                $form_element_factory,
                $description_field_retriever,
                $title_field_retriever,
            ),
        );
    }

    private function getRepresentationTransformer(
        ArtidocWithContext $artidoc,
        \PFUser $user,
    ): RetrievedSectionsToRepresentationTransformer {
        return new RetrievedSectionsToRepresentationTransformer(
            $this->getSectionRepresentationBuilder($artidoc, $user),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(
                    \Tracker_ArtifactFactory::instance(),
                    CachedSemanticDescriptionFieldRetriever::instance(),
                    CachedSemanticTitleFieldRetriever::instance(),
                )
            ),
        );
    }

    private function getSectionRepresentationBuilder(
        ArtidocWithContext $artidoc,
        \PFUser $user,
    ): SectionRepresentationBuilder {
        return new SectionRepresentationBuilder($this->getArtifactSectionRepresentationBuilder($artidoc, $user));
    }

    private function getArtidocWithContextRetriever(\PFUser $user): RetrieveArtidocWithContext
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $retriever_builder = new ArtidocWithContextRetrieverBuilder(
            new ArtidocRetriever(new SearchArtidocDocumentDao(), new Docman_ItemFactory()),
            new ArtidocWithContextDecorator(
                \ProjectManager::instance(),
                new DocumentServiceFromAllowedProjectRetriever($plugin),
            ),
        );

        return $retriever_builder->buildForUser($user);
    }

    private function getArtifactSectionRepresentationBuilder(
        ArtidocWithContext $artidoc,
        \PFUser $user,
    ): ArtifactSectionRepresentationBuilder {
        $form_element_factory  = \Tracker_FormElementFactory::instance();
        $title_field_retriever = CachedSemanticTitleFieldRetriever::instance();

        $configured_field_collection_builder = new ConfiguredFieldCollectionBuilder(
            new ConfiguredFieldDao(),
            new SuitableFieldRetriever(
                $form_element_factory,
                CachedSemanticDescriptionFieldRetriever::instance(),
                $title_field_retriever,
            ),
        );

        $provide_user_avatar_url = new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash());
        $user_manager            = UserManager::instance();
        $purifier                = Codendi_HTMLPurifier::instance();
        $text_value_interpreter  = new TextValueInterpreter($purifier, CommonMarkInterpreter::build($purifier));

        return new ArtifactSectionRepresentationBuilder(
            new FileUploadDataProvider(
                new FrozenFieldDetector(
                    new TransitionRetriever(
                        new StateFactory(
                            \TransitionFactory::instance(),
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
            ),
            new FieldsWithValuesBuilder(
                $configured_field_collection_builder->buildFromArtidoc($artidoc, $user),
                new ListFieldWithValueBuilder(
                    new UserListFieldWithValueBuilder(
                        $user_manager,
                        $provide_user_avatar_url,
                        $provide_user_avatar_url,
                    ),
                    new StaticListFieldWithValueBuilder(),
                    new UserGroupListWithValueBuilder(),
                ),
                new ArtifactLinkFieldWithValueBuilder(
                    $user,
                    $title_field_retriever,
                    CachedSemanticStatusRetriever::instance(),
                    new TypePresenterFactory(new TypeDao(), new ArtifactLinksUsageDao(), new SystemTypePresenterBuilder(EventManager::instance())),
                ),
                new NumericFieldWithValueBuilder(new PriorityDao()),
                new UserFieldWithValueBuilder(
                    $user_manager,
                    $user_manager,
                    $provide_user_avatar_url,
                    $provide_user_avatar_url,
                    UserHelper::instance(),
                ),
                new DateFieldWithValueBuilder($user),
                new PermissionsOnArtifactFieldWithValueBuilder(),
                new StepsDefinitionFieldWithValueBuilder($text_value_interpreter),
                new StepsExecutionFieldWithValueBuilder($text_value_interpreter),
            )
        );
    }

    private function getSectionOrderBuilder(): SectionOrderBuilder
    {
        return new SectionOrderBuilder($this->getSectionIdentifierFactory());
    }

    private function getSectionIdentifierFactory(): SectionIdentifierFactory
    {
        return new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());
    }

    private function getFreetextIdentifierFactory(): FreetextIdentifierFactory
    {
        return new UUIDFreetextIdentifierFactory(new DatabaseUUIDV7Factory());
    }
}
