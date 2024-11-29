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
use Tuleap\Artidoc\Adapter\Document\CurrentCurrentUserHasArtidocPermissionsChecker;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Document\ArtidocDao;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContextRetriever;
use Tuleap\Artidoc\Document\DocumentServiceFromAllowedProjectRetriever;
use Tuleap\Artidoc\Document\Tracker\NoSemanticDescriptionFault;
use Tuleap\Artidoc\Document\Tracker\NoSemanticTitleFault;
use Tuleap\Artidoc\Document\Tracker\SemanticTitleIsNotAStringFault;
use Tuleap\Artidoc\Document\Tracker\SuitableTrackerForDocumentChecker;
use Tuleap\Artidoc\Document\Tracker\TooManyRequiredFieldsFault;
use Tuleap\Artidoc\Document\Tracker\TrackerNotFoundFault;
use Tuleap\Artidoc\Domain\Document\Order\CannotMoveSectionRelativelyToItselfFault;
use Tuleap\Artidoc\Domain\Document\Order\Direction;
use Tuleap\Artidoc\Domain\Document\Order\InvalidComparedToFault;
use Tuleap\Artidoc\Domain\Document\Order\InvalidDirectionFault;
use Tuleap\Artidoc\Domain\Document\Order\InvalidIdsFault;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrderBuilder;
use Tuleap\Artidoc\Domain\Document\Order\UnableToReorderSectionOutsideOfDocumentFault;
use Tuleap\Artidoc\Domain\Document\Order\UnknownSectionToMoveFault;
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
        Header::allowOptionsGetPutPostPatch();
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
     * @return ArtidocSectionRepresentation[]
     *
     * @status 200
     * @throws RestException
     */
    public function getSections(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $this->checkAccess();

        $user = UserManager::instance()->getCurrentUser();
        return $this->getBuilder($user)
            ->build($id, $limit, $offset, $user)
            ->match(
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
     * Set sections
     *
     * Set sections of an artidoc document.
     *
     * <p>The sections will be saved in the given order.</p>
     *
     * <p>Note: do not use this route to reorder sections since it will change the section ids.</p>
     *
     * <p>Example payload:</p>
     * <pre>
     * [<br>
     * &nbsp;&nbsp;{ "artifact": { "id": 123 } },<br>
     * &nbsp;&nbsp;{ "artifact": { "id": 426 } },<br>
     * ]
     * </pre>
     *
     * @url    PUT {id}/sections
     * @access hybrid
     *
     * @param int $id Id of the document
     * @param array $sections {@from body} {@type \Tuleap\Artidoc\REST\v1\ArtidocPUTSectionRepresentation}
     *
     * @status 200
     * @throws RestException
     */
    public function putSections(int $id, array $sections): void
    {
        $this->checkAccess();

        $user = UserManager::instance()->getCurrentUser();
        $this->getPutHandler($user)
            ->handle($id, $sections, $user)
            ->match(
                static function () {
                    // nothing to do
                },
                function (Fault $fault) {
                    Fault::writeToLogger($fault, RESTLogger::getLogger());
                    if ($fault instanceof UserCannotReadSectionFault) {
                        throw new I18NRestException(
                            403,
                            dgettext('tuleap-artidoc', 'Unable to set the sections of the document, at least one of the submitted section does not exist.')
                        );
                    }
                    throw new RestException(404);
                }
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
        $this->getPatchHandler($user)
            ->handle($id, $order)
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
    public function postSection(int $id, ArtidocPOSTSectionRepresentation $section): ArtidocSectionRepresentation
    {
        $this->checkAccess();

        $user = UserManager::instance()->getCurrentUser();
        return $this->getPostHandler($user)
            ->handle($id, $section, $user)
            ->match(
                static function (ArtidocSectionRepresentation $representation) {
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

    private function getBuilder(\PFUser $user): PaginatedArtidocSectionRepresentationCollectionBuilder
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $dao       = new ArtidocDao(new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory()));
        $retriever = new ArtidocWithContextRetriever(
            new ArtidocRetriever($dao, new Docman_ItemFactory()),
            CurrentCurrentUserHasArtidocPermissionsChecker::withCurrentUser($user),
            new ArtidocWithContextDecorator(
                \ProjectManager::instance(),
                new DocumentServiceFromAllowedProjectRetriever($plugin),
            ),
        );

        $transformer = $this->getRepresentationTransformer();

        return new PaginatedArtidocSectionRepresentationCollectionBuilder($retriever, $dao, $transformer);
    }

    /**
     * @throws RestException
     */
    private function getPutHandler(\PFUser $user): PUTSectionsHandler
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $identifier_factory = new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $retriever          = new ArtidocWithContextRetriever(
            new ArtidocRetriever($dao, new Docman_ItemFactory()),
            CurrentCurrentUserHasArtidocPermissionsChecker::withCurrentUser($user),
            new ArtidocWithContextDecorator(
                \ProjectManager::instance(),
                new DocumentServiceFromAllowedProjectRetriever($plugin),
            ),
        );

        $transformer = $this->getRepresentationTransformer();

        return new PUTSectionsHandler(
            $retriever,
            $transformer,
            $dao,
            $identifier_factory,
        );
    }

    /**
     * @throws RestException
     */
    private function getPostHandler(\PFUser $user): POSTSectionHandler
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $identifier_factory = new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $retriever          = new ArtidocWithContextRetriever(
            new ArtidocRetriever($dao, new Docman_ItemFactory()),
            CurrentCurrentUserHasArtidocPermissionsChecker::withCurrentUser($user),
            new ArtidocWithContextDecorator(
                \ProjectManager::instance(),
                new DocumentServiceFromAllowedProjectRetriever($plugin),
            ),
        );

        $transformer = $this->getRepresentationTransformer();

        return new POSTSectionHandler(
            $retriever,
            $transformer,
            $dao,
            $identifier_factory,
        );
    }

    /**
     * @throws RestException
     */
    private function getPatchHandler(\PFUser $user): PATCHSectionsHandler
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $identifier_factory = new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $retriever          = new ArtidocWithContextRetriever(
            new ArtidocRetriever($dao, new Docman_ItemFactory()),
            CurrentCurrentUserHasArtidocPermissionsChecker::withCurrentUser($user),
            new ArtidocWithContextDecorator(
                \ProjectManager::instance(),
                new DocumentServiceFromAllowedProjectRetriever($plugin),
            ),
        );

        return new PATCHSectionsHandler(
            $retriever,
            new SectionOrderBuilder($identifier_factory),
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

        $dao       = new ArtidocDao(new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory()));
        $retriever = new ArtidocWithContextRetriever(
            new ArtidocRetriever($dao, new Docman_ItemFactory()),
            CurrentCurrentUserHasArtidocPermissionsChecker::withCurrentUser($user),
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

    private function getRepresentationTransformer(): RawSectionsToRepresentationTransformer
    {
        $form_element_factory = \Tracker_FormElementFactory::instance();
        $transformer          = new RawSectionsToRepresentationTransformer(
            new \Tracker_ArtifactDao(),
            \Tracker_ArtifactFactory::instance(),
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
            )
        );
        return $transformer;
    }
}
