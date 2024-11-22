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
use Tuleap\Artidoc\Document\ArtidocDao;
use Tuleap\Artidoc\Document\ArtidocRetriever;
use Tuleap\Artidoc\Document\DocumentServiceFromAllowedProjectRetriever;
use Tuleap\Artidoc\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Document\Tracker\NoSemanticDescriptionFault;
use Tuleap\Artidoc\Document\Tracker\NoSemanticTitleFault;
use Tuleap\Artidoc\Document\Tracker\SemanticTitleIsNotAStringFault;
use Tuleap\Artidoc\Document\Tracker\SuitableTrackerForDocumentChecker;
use Tuleap\Artidoc\Document\Tracker\TooManyRequiredFieldsFault;
use Tuleap\Artidoc\Document\Tracker\TrackerNotFoundFault;
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
        Header::allowOptionsGetPutPost();
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

        return $this->getBuilder()
            ->build($id, $limit, $offset, UserManager::instance()->getCurrentUser())
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

        $this->getPutHandler()
            ->handle($id, $sections, UserManager::instance()->getCurrentUser())
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

        return $this->getPostHandler()
            ->handle($id, $section, UserManager::instance()->getCurrentUser())
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

        $this->getPutConfigurationHandler()
            ->handle($id, $configuration, UserManager::instance()->getCurrentUser())
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

    private function getBuilder(): PaginatedArtidocSectionRepresentationCollectionBuilder
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $dao       = new ArtidocDao(new SectionIdentifierFactory(new DatabaseUUIDV7Factory()));
        $retriever = new ArtidocRetriever(
            \ProjectManager::instance(),
            $dao,
            new Docman_ItemFactory(),
            new DocumentServiceFromAllowedProjectRetriever($plugin),
        );

        $transformer = $this->getRepresentationTransformer();

        return new PaginatedArtidocSectionRepresentationCollectionBuilder($retriever, $dao, $transformer);
    }

    /**
     * @throws RestException
     */
    private function getPutHandler(): PUTSectionsHandler
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $identifier_factory = new SectionIdentifierFactory(new DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $retriever          = new ArtidocRetriever(
            \ProjectManager::instance(),
            $dao,
            new Docman_ItemFactory(),
            new DocumentServiceFromAllowedProjectRetriever($plugin),
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
    private function getPostHandler(): POSTSectionHandler
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $identifier_factory = new SectionIdentifierFactory(new DatabaseUUIDV7Factory());
        $dao                = new ArtidocDao($identifier_factory);
        $retriever          = new ArtidocRetriever(
            \ProjectManager::instance(),
            $dao,
            new Docman_ItemFactory(),
            new DocumentServiceFromAllowedProjectRetriever($plugin),
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
    private function getPutConfigurationHandler(): PUTConfigurationHandler
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $dao       = new ArtidocDao(new SectionIdentifierFactory(new DatabaseUUIDV7Factory()));
        $retriever = new ArtidocRetriever(
            \ProjectManager::instance(),
            $dao,
            new Docman_ItemFactory(),
            new DocumentServiceFromAllowedProjectRetriever($plugin),
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
