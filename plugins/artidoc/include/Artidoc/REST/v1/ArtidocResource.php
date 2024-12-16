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

use DateTimeImmutable;
use Docman_ItemFactory;
use Docman_PermissionsManager;
use EventManager;
use Luracast\Restler\RestException;
use ProjectManager;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\ArtidocRetriever;
use Tuleap\Artidoc\Adapter\Document\ArtidocWithContextDecorator;
use Tuleap\Artidoc\Adapter\Document\CurrentUserHasArtidocPermissionsChecker;
use Tuleap\Artidoc\Adapter\Document\SearchArtidocDocumentDao;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\ReorderSectionsDao;
use Tuleap\Artidoc\Adapter\Document\Section\RequiredSectionInformationCollector;
use Tuleap\Artidoc\Adapter\Document\Section\RetrieveArtidocSectionDao;
use Tuleap\Artidoc\Adapter\Document\Section\SaveSectionDao;
use Tuleap\Artidoc\Document\ArtidocDao;
use Tuleap\Artidoc\Document\DocumentServiceFromAllowedProjectRetriever;
use Tuleap\Artidoc\Document\Tracker\NoSemanticDescriptionFault;
use Tuleap\Artidoc\Document\Tracker\NoSemanticTitleFault;
use Tuleap\Artidoc\Document\Tracker\SemanticTitleIsNotAStringFault;
use Tuleap\Artidoc\Document\Tracker\SuitableTrackerForDocumentChecker;
use Tuleap\Artidoc\Document\Tracker\TooManyRequiredFieldsFault;
use Tuleap\Artidoc\Document\Tracker\TrackerNotFoundFault;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContextRetriever;
use Tuleap\Artidoc\Domain\Document\Order\CannotMoveSectionRelativelyToItselfFault;
use Tuleap\Artidoc\Domain\Document\Order\ChangeSectionOrder;
use Tuleap\Artidoc\Domain\Document\Order\Direction;
use Tuleap\Artidoc\Domain\Document\Order\InvalidComparedToFault;
use Tuleap\Artidoc\Domain\Document\Order\InvalidDirectionFault;
use Tuleap\Artidoc\Domain\Document\Order\InvalidIdsFault;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrder;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrderBuilder;
use Tuleap\Artidoc\Domain\Document\Order\UnableToReorderSectionOutsideOfDocumentFault;
use Tuleap\Artidoc\Domain\Document\Order\UnknownSectionToMoveFault;
use Tuleap\Artidoc\Domain\Document\Section\AlreadyExistingSectionWithSameArtifactFault;
use Tuleap\Artidoc\Domain\Document\Section\CollectRequiredSectionInformation;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\InvalidSectionIdentifierStringException;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\PaginatedRawSections;
use Tuleap\Artidoc\Domain\Document\Section\PaginatedRawSectionsRetriever;
use Tuleap\Artidoc\Domain\Document\Section\SectionCreator;
use Tuleap\Artidoc\Domain\Document\Section\UnableToFindSiblingSectionFault;
use Tuleap\Artidoc\Domain\Document\UserCannotWriteDocumentFault;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\REST\v1\DocmanItemsEventAdder;
use Tuleap\Docman\REST\v1\DocmanItemsRequestBuilder;
use Tuleap\Docman\REST\v1\DocmanPATCHItemRepresentation;
use Tuleap\Docman\REST\v1\MoveItem\BeforeMoveVisitor;
use Tuleap\Docman\REST\v1\MoveItem\DocmanItemMover;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadDAO;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\NeverThrow\Fault;
use Tuleap\Option\Option;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\RESTLogger;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use UserManager;

final class ArtidocResource extends AuthenticatedResource
{
    public const ROUTE      = 'artidoc';
    private const MAX_LIMIT = 50;

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

        return $this->getPaginatedRawSectionsRetriever($user)
            ->retrievePaginatedRawSections($id, $limit, $offset)
            ->andThen(fn (PaginatedRawSections $raw_sections) =>
                $this->getRepresentationTransformer($user)->getRepresentation($raw_sections, $user))->match(
                    function (PaginatedArtidocSectionRepresentationCollection $collection) use ($limit, $offset) {
                        Header::sendPaginationHeaders($limit, $offset, $collection->total, self::MAX_LIMIT);
                        return $collection->sections;
                    },
                    function (Fault $fault) {
                        Fault::writeToLogger($fault, RESTLogger::getLogger());
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
            ->match(
                static function () {
                    // nothing to do
                },
                static function (Fault $fault) use ($order) {
                    Fault::writeToLogger($fault, RESTLogger::getLogger());
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
                        default => new RestException(404, (string) $fault),
                    };
                }
            );
    }

    /**
     * Create section
     *
     * Create one section in an artidoc document.
     *
     * <p>Example payload, to create a section based on artifact #123. The new section will be placed before its sibling:</p>
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"artifact": { "id": 123 },<br>
     * &nbsp;&nbsp;"position": { "before": "550e8400-e29b-41d4-a716-446655440000" },<br>
     * }
     * </pre>
     *
     * <p>Another example, if you want to put the section at the end of the document:</p>
     * <pre>
     * {<br>
     * &nbsp;&nbsp;"artifact": { "id": 123 },<br>
     * &nbsp;&nbsp;"position": null,<br>
     * }
     * </pre>
     *
     * @url    POST {id}/sections
     * @access hybrid
     *
     * @param int $id Id of the document
     * @param ArtidocPOSTSectionRepresentation $section {@from body}
     *
     * @status 200
     * @throws RestException
     */
    public function postSection(int $id, ArtidocPOSTSectionRepresentation $section): ArtifactSectionRepresentation
    {
        $this->checkAccess();

        $user = UserManager::instance()->getCurrentUser();

        $identifier_factory = new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());

        $collector = new RequiredSectionInformationCollector(
            $user,
            new RequiredArtifactInformationBuilder(\Tracker_ArtifactFactory::instance())
        );

        try {
            $before_section_id = $section->position
                ? Option::fromValue($identifier_factory->buildFromHexadecimalString($section->position->before))
                : Option::nothing(SectionIdentifier::class);
        } catch (InvalidSectionIdentifierStringException) {
            throw new RestException(400, 'Sibling section id is invalid');
        }

        return $this->getSectionCreator($user, $collector)
            ->create($id, $section->artifact->id, $before_section_id)
            ->andThen(function (SectionIdentifier $section_identifier) use ($collector, $section) {
                return $collector->getCollectedRequiredSectionInformation($section->artifact->id)
                    ->map(fn(RequiredArtifactInformation $info) => new SectionWrapper($section_identifier, $info));
            })
            ->map(fn (SectionWrapper $created) => $this->getRepresentationBuilder()->build($created->required_info, $created->section_identifier, $user))
            ->match(
                static function (ArtifactSectionRepresentation $representation) {
                    return $representation;
                },
                static function (Fault $fault) {
                    Fault::writeToLogger($fault, RESTLogger::getLogger());
                    throw match (true) {
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
     * @url    PUT {id}/configuration
     * @access hybrid
     *
     * @param int $id Id of the document
     * @param ArtidocPUTConfigurationRepresentation $configuration {@from body}
     *
     * @status 200
     * @throws RestException
     */
    public function putConfiguration(int $id, ArtidocPUTConfigurationRepresentation $configuration): void
    {
        $this->checkAccess();

        $user = UserManager::instance()->getCurrentUser();
        $this->getPutConfigurationHandler($user)
            ->handle($id, $configuration, $user)
            ->match(
                static function () {
                    // nothing to do
                },
                function (Fault $fault) {
                    Fault::writeToLogger($fault, RESTLogger::getLogger());
                    throw match (true) {
                        $fault instanceof TrackerNotFoundFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', "Given tracker cannot be found or you don't have access to it.")
                        ),
                        $fault instanceof NoSemanticTitleFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'Given tracker does not have a semantic title.')
                        ),
                        $fault instanceof NoSemanticDescriptionFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'Given tracker does not have a semantic description.')
                        ),
                        $fault instanceof SemanticTitleIsNotAStringFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'The semantic title should be a string field.')
                        ),
                        $fault instanceof TooManyRequiredFieldsFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'There cannot be other required fields than title or description.')
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

    private function getPaginatedRawSectionsRetriever(\PFUser $user): PaginatedRawSectionsRetriever
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $uuid_factory = new DatabaseUUIDV7Factory();
        $dao          = new RetrieveArtidocSectionDao(new UUIDSectionIdentifierFactory($uuid_factory), new UUIDFreetextIdentifierFactory($uuid_factory));
        $retriever    = new ArtidocWithContextRetriever(
            new ArtidocRetriever(new SearchArtidocDocumentDao(), new Docman_ItemFactory()),
            CurrentUserHasArtidocPermissionsChecker::withCurrentUser($user),
            new ArtidocWithContextDecorator(
                \ProjectManager::instance(),
                new DocumentServiceFromAllowedProjectRetriever($plugin),
            ),
        );

        return new PaginatedRawSectionsRetriever($retriever, $dao);
    }

    /**
     * @throws RestException
     */
    private function getSectionCreator(\PFUser $user, CollectRequiredSectionInformation $collector): SectionCreator
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $uuid_factory       = new DatabaseUUIDV7Factory();
        $identifier_factory = new UUIDSectionIdentifierFactory($uuid_factory);
        $dao                = new SaveSectionDao($identifier_factory, new UUIDFreetextIdentifierFactory($uuid_factory));
        $retriever          = new ArtidocWithContextRetriever(
            new ArtidocRetriever(new SearchArtidocDocumentDao(), new Docman_ItemFactory()),
            CurrentUserHasArtidocPermissionsChecker::withCurrentUser($user),
            new ArtidocWithContextDecorator(
                \ProjectManager::instance(),
                new DocumentServiceFromAllowedProjectRetriever($plugin),
            ),
        );

        return new SectionCreator(
            $retriever,
            $dao,
            $collector,
        );
    }

    private function getRepresentationBuilder(): ArtifactSectionRepresentationBuilder
    {
        $form_element_factory = \Tracker_FormElementFactory::instance();

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
                        $form_element_factory,
                    )
                ),
                $form_element_factory,
            )
        );
    }

    /**
     * @throws RestException
     */
    private function getReorderHandler(\PFUser $user): ChangeSectionOrder
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $dao       = new ReorderSectionsDao();
        $retriever = new ArtidocWithContextRetriever(
            new ArtidocRetriever(new SearchArtidocDocumentDao(), new Docman_ItemFactory()),
            CurrentUserHasArtidocPermissionsChecker::withCurrentUser($user),
            new ArtidocWithContextDecorator(
                \ProjectManager::instance(),
                new DocumentServiceFromAllowedProjectRetriever($plugin),
            ),
        );

        return new ChangeSectionOrder(
            $retriever,
            $dao,
        );
    }

    /**
     * @throws RestException
     */
    private function getPutConfigurationHandler(\PFUser $user): PUTConfigurationHandler
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $uuid_factory = new DatabaseUUIDV7Factory();
        $dao          = new ArtidocDao(new UUIDSectionIdentifierFactory($uuid_factory), new UUIDFreetextIdentifierFactory($uuid_factory));
        $retriever    = new ArtidocWithContextRetriever(
            new ArtidocRetriever(new SearchArtidocDocumentDao(), new Docman_ItemFactory()),
            CurrentUserHasArtidocPermissionsChecker::withCurrentUser($user),
            new ArtidocWithContextDecorator(
                \ProjectManager::instance(),
                new DocumentServiceFromAllowedProjectRetriever($plugin),
            ),
        );

        return new PUTConfigurationHandler(
            $retriever,
            $dao,
            \TrackerFactory::instance(),
            new SuitableTrackerForDocumentChecker(\Tracker_FormElementFactory::instance()),
        );
    }

    private function getRepresentationTransformer(\PFUser $user): RawSectionsToRepresentationTransformer
    {
        return new RawSectionsToRepresentationTransformer(
            $this->getSectionRepresentationBuilder(),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(\Tracker_ArtifactFactory::instance())
            ),
        );
    }

    private function getSectionRepresentationBuilder(): SectionRepresentationBuilder
    {
        return new SectionRepresentationBuilder($this->getArtifactSectionRepresentationBuilder());
    }

    private function getArtifactSectionRepresentationBuilder(): ArtifactSectionRepresentationBuilder
    {
        $form_element_factory = \Tracker_FormElementFactory::instance();

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
        );
    }

    private function getSectionOrderBuilder(): SectionOrderBuilder
    {
        return (new SectionOrderBuilder(new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory())));
    }
}
