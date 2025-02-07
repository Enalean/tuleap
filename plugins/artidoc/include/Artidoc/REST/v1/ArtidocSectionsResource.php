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

use Docman_ItemFactory;
use Luracast\Restler\RestException;
use Tuleap\Artidoc\Adapter\Document\ArtidocRetriever;
use Tuleap\Artidoc\Adapter\Document\ArtidocWithContextDecorator;
use Tuleap\Artidoc\Adapter\Document\SearchArtidocDocumentDao;
use Tuleap\Artidoc\Adapter\Document\Section\AlreadyExistingSectionWithSameArtifactFault;
use Tuleap\Artidoc\Adapter\Document\Section\DeleteOneSectionDao;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\UpdateFreetextContentDao;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\RequiredSectionInformationCollector;
use Tuleap\Artidoc\Adapter\Document\Section\RetrieveArtidocSectionDao;
use Tuleap\Artidoc\Adapter\Document\Section\SaveSectionDao;
use Tuleap\Artidoc\Adapter\Document\Section\UnableToFindSiblingSectionFault;
use Tuleap\Artidoc\ArtidocWithContextRetrieverBuilder;
use Tuleap\Artidoc\Document\DocumentServiceFromAllowedProjectRetriever;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
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
use Tuleap\Artidoc\Domain\Document\Section\UnableToUpdateArtifactSectionFault;
use Tuleap\Artidoc\Domain\Document\UserCannotWriteDocumentFault;
use Tuleap\DB\DatabaseUUIDV7Factory;
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
            new RequiredArtifactInformationBuilder(\Tracker_ArtifactFactory::instance())
        );


        return $this->getSectionRetriever($user, $collector)
            ->retrieveSectionUserCanRead($section_id)
            ->andThen(fn(RetrievedSection $section) =>
                $this->getSectionRepresentationBuilder()->getSectionRepresentation($section, $collector, $user))->match(
                    fn(SectionRepresentation $representation) => $representation,
                    function (Fault $fault) {
                        Fault::writeToLogger($fault, RESTLogger::getLogger());
                        throw new RestException(404);
                    },
                );
    }

    /**
     * Update section
     *
     * Update the content of a section (title, description)
     *
     * <p><b>Note:</b> Only freetext section can be updated via this route.
     * To update an artifact section, you should use artifact dedicated route.
     * </p>
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
            new RequiredArtifactInformationBuilder(\Tracker_ArtifactFactory::instance())
        );

        $updater = new SectionUpdater($this->getSectionRetriever($user, $collector), new UpdateFreetextContentDao());

        $updater->update($section_id, $content->title, $content->description, $level)
            ->mapErr(
                function (Fault $fault) {
                    Fault::writeToLogger($fault, RESTLogger::getLogger());
                    throw match (true) {
                        $fault instanceof EmptyTitleFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'Title of the section cannot be empty.')
                        ),
                        $fault instanceof UserCannotWriteDocumentFault => new I18NRestException(
                            403,
                            dgettext('tuleap-artidoc', "You don't have permission to write the document.")
                        ),
                        $fault instanceof UnableToUpdateArtifactSectionFault => new I18NRestException(
                            400,
                            dgettext('tuleap-artidoc', 'Artifact sections cannot be updated via this route.')
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
                function (Fault $fault) {
                    Fault::writeToLogger($fault, RESTLogger::getLogger());
                    throw new RestException(404);
                },
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
     *  <p>Example payload, to create a section based on free text. The new section will be placed before its sibling:</p>
     *  <pre>
     * {<br>
     * &nbsp;&nbsp;id: 456,<br>
     * &nbsp;&nbsp;section:{<br>
     *  &nbsp;&nbsp;&nbsp;&nbsp;"content": { "title": "My title", "description": "My freetext description", type: "freetext" },<br>
     *  &nbsp;&nbsp;&nbsp;&nbsp;"position": { "before": "550e8400-e29b-41d4-a716-446655440000" },<br>
     * &nbsp;&nbsp;}<br>
     *  }
     *  </pre>
     *
     * @url    POST
     * @access hybrid
     *
     * @param int $artidoc_id Id of the document {@from body}
     * @param ArtidocSectionPOSTRepresentation $section {@from body}
     *
     * @status 200
     * @throws RestException
     */
    public function postSection(int $artidoc_id, ArtidocSectionPOSTRepresentation $section): SectionRepresentation
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

        $level = Level::tryFrom($section->level);
        if ($level === null) {
            throw new RestException(400, 'Unknown level. Allowed values: ' . implode(', ', Level::allowed()));
        }

        return $this->getSectionCreator($user, $collector)
            ->create($artidoc_id, $before_section_id, $level, ContentToBeCreatedBuilder::buildFromRepresentation($section))
            ->andThen(
                fn (SectionIdentifier $section_identifier) =>
                $this->getSectionRetriever($user, $collector)
                    ->retrieveSectionUserCanRead($section_identifier)
            )->andThen(
                fn (RetrievedSection $section) =>
                $this->getSectionRepresentationBuilder()
                    ->getSectionRepresentation($section, $collector, $user)
            )
            ->match(
                static function (SectionRepresentation $representation) {
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
     * @throws RestException
     */
    private function getSectionCreator(\PFUser $user, CollectRequiredSectionInformation $collector): SectionCreator
    {
        return new SectionCreator(
            $this->getArtidocWithContextRetriever($user),
            new SaveSectionDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory()),
            $collector,
        );
    }

    private function getDeleteHandler(\PFUser $user): SectionDeletor
    {
        return new SectionDeletor(
            new RetrieveArtidocSectionDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory()),
            $this->getArtidocWithContextRetriever($user),
            new DeleteOneSectionDao(),
        );
    }

    private function getSectionRetriever(\PFUser $user, CollectRequiredSectionInformation $collector): SectionRetriever
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
}
