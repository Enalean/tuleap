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
use Tuleap\Artidoc\Adapter\Document\CurrentUserHasArtidocPermissionsChecker;
use Tuleap\Artidoc\Adapter\Document\SearchArtidocDocumentDao;
use Tuleap\Artidoc\Adapter\Document\Section\DeleteOneSectionDao;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\RequiredSectionInformationCollector;
use Tuleap\Artidoc\Adapter\Document\Section\RetrieveArtidocSectionDao;
use Tuleap\Artidoc\Document\DocumentServiceFromAllowedProjectRetriever;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContextRetriever;
use Tuleap\Artidoc\Domain\Document\Section\CollectRequiredSectionInformation;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\Identifier\FreetextIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\RawSection;
use Tuleap\Artidoc\Domain\Document\Section\SectionRetriever;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\InvalidSectionIdentifierStringException;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\SectionDeletor;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Fault;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
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
        Header::allowOptionsGetDelete();
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
            ->retrieveSection($section_id)
            ->andThen(fn(RawSection $section) =>
                $this->getSectionRepresentationBuilder()->getSectionRepresentation($section, $collector, $user))->match(
                    fn(SectionRepresentation $representation) => $representation,
                    function (Fault $fault) {
                        Fault::writeToLogger($fault, RESTLogger::getLogger());
                        throw new RestException(404);
                    },
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

    private function getDeleteHandler(\PFUser $user): SectionDeletor
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $retriever = new ArtidocWithContextRetriever(
            new ArtidocRetriever(new SearchArtidocDocumentDao(), new Docman_ItemFactory()),
            CurrentUserHasArtidocPermissionsChecker::withCurrentUser($user),
            new ArtidocWithContextDecorator(
                \ProjectManager::instance(),
                new DocumentServiceFromAllowedProjectRetriever($plugin),
            ),
        );

        $section_identifier_factory  = $this->getSectionIdentifierFactory();
        $freetext_identifier_factory = $this->getFreetextIdentifierFactory();

        return new SectionDeletor(
            new RetrieveArtidocSectionDao($section_identifier_factory, $freetext_identifier_factory),
            $retriever,
            new DeleteOneSectionDao(),
        );
    }

    private function getSectionRetriever(\PFUser $user, CollectRequiredSectionInformation $collector): SectionRetriever
    {
        $plugin = \PluginManager::instance()->getEnabledPluginByName('artidoc');
        if (! $plugin) {
            throw new RestException(404);
        }

        $dao       = new RetrieveArtidocSectionDao($this->getSectionIdentifierFactory(), $this->getFreetextIdentifierFactory());
        $retriever = new ArtidocWithContextRetriever(
            new ArtidocRetriever(new SearchArtidocDocumentDao(), new Docman_ItemFactory()),
            CurrentUserHasArtidocPermissionsChecker::withCurrentUser($user),
            new ArtidocWithContextDecorator(
                \ProjectManager::instance(),
                new DocumentServiceFromAllowedProjectRetriever($plugin),
            ),
        );

        return new SectionRetriever($dao, $retriever, $collector);
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
}
