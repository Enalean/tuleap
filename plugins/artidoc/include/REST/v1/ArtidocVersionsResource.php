<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
use Docman_ItemFactory;
use EventManager;
use Luracast\Restler\RestException;
use Tuleap\Artidoc\Adapter\Document\ArtidocRetriever;
use Tuleap\Artidoc\Adapter\Document\ArtidocWithContextDecorator;
use Tuleap\Artidoc\Adapter\Document\SearchArtidocDocumentDao;
use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\RequiredSectionInformationCollector;
use Tuleap\Artidoc\Adapter\Document\Section\RetrieveArtidocSectionDao;
use Tuleap\Artidoc\Adapter\Document\Section\Versions\ChangesetBeforeAGivenOneRetriever;
use Tuleap\Artidoc\Adapter\Document\Section\Versions\VersionedSectionsDAO;
use Tuleap\Artidoc\ArtidocWithContextRetrieverBuilder;
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
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\PaginatedRetrievedSections;
use Tuleap\Artidoc\Domain\Document\Section\PaginatedRetrievedSectionsRetriever;
use Tuleap\Artidoc\Domain\Document\Section\Versions\VersionBelongsToASectionOfTheArtidocChecker;
use Tuleap\Artidoc\Domain\Document\Section\Versions\VersionDoesNotBelongToASectionOfCurrentArtidocFault;
use Tuleap\Artidoc\REST\v1\ArtifactSection\ArtifactSectionRepresentationBuilder;
use Tuleap\Artidoc\REST\v1\ArtifactSection\RequiredArtifactInformationBuilder;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Markdown\CommonMarkInterpreter;
use Tuleap\NeverThrow\Fault;
use Tuleap\Option\Option;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
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

final class ArtidocVersionsResource extends AuthenticatedResource
{
    public const string ROUTE   = 'artidoc_versions';
    private const int MAX_LIMIT = 50;

    /**
     * @url OPTIONS {id}
     */
    public function optionsVersions(string $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * @url GET {id}
     *
     * @access hybrid
     * @hide This route exists for a prototyping purpose only.
     *
     * @param int $id Id of the version
     * @param int $artidoc_id Id of the document {@from path}{@min 1}
     * @param int $limit Number of elements displayed {@from path}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @return SectionRepresentation[]
     *
     * @status 200
     * @throws RestException
     */
    public function getVersions(int $id, int $artidoc_id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $this->checkAccess();

        $user            = UserManager::instance()->getCurrentUser();
        $builder         = new PaginatedRetrievedSectionsRetriever(
            $this->getArtidocWithContextRetriever($user),
            new RetrieveArtidocSectionDao(new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory()), new UUIDFreetextIdentifierFactory(new DatabaseUUIDV7Factory())),
        );
        $version_checker = new VersionBelongsToASectionOfTheArtidocChecker(
            new VersionedSectionsDAO()
        );

        $before_changeset_id = Option::fromValue($id);

        return $builder->retrievePaginatedRetrievedSections($artidoc_id, $before_changeset_id, $limit, $offset)
            ->andThen(
                static fn(
                    PaginatedRetrievedSections $retrieved_sections,
                ) => $version_checker->checkVersionBelongsToAnArtidocSection($retrieved_sections->artidoc, $id)
                    ->map(static fn () => $retrieved_sections)
            )->andThen(
                fn(PaginatedRetrievedSections $retrieved_sections) => $this->getRepresentationTransformer(
                    $retrieved_sections->artidoc,
                    $user
                )->getRepresentation($retrieved_sections, $user, $before_changeset_id)
            )->match(
                function (PaginatedArtidocSectionRepresentationCollection $collection) use ($limit, $offset) {
                    Header::sendPaginationHeaders($limit, $offset, $collection->total, self::MAX_LIMIT);
                    return $collection->sections;
                },
                static function (Fault $fault) {
                    throw match ($fault::class) {
                        VersionDoesNotBelongToASectionOfCurrentArtidocFault::class => new RestException(400, (string) $fault),
                        default => new RestException(404),
                    };
                },
            );
    }

    private function getRepresentationTransformer(
        ArtidocWithContext $artidoc,
        \PFUser $user,
    ): RetrievedSectionsToRepresentationTransformer {
        return new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder($this->getArtifactSectionRepresentationBuilder($artidoc, $user)),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(
                    \Tracker_ArtifactFactory::instance(),
                    CachedSemanticDescriptionFieldRetriever::instance(),
                    CachedSemanticTitleFieldRetriever::instance(),
                    new ChangesetBeforeAGivenOneRetriever()
                )
            ),
        );
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
